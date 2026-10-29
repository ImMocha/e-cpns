import test from 'node:test';
import assert from 'node:assert/strict';
import { TryoutDemo } from '../resources/js/lontar/tryout-demo.js';

test('timer waits for start and uses elapsed time even after a delayed tick', () => {
    const demo = new TryoutDemo();
    assert.equal(demo.remaining(999_999), 180);
    demo.answer(0, 1000);
    assert.deepEqual(demo.answers, [null, null, null]);

    demo.start(1000);
    demo.start(5000);

    assert.equal(demo.remaining(62_000), 119);
    assert.equal(demo.remaining(181_000), 0);
    assert.equal(demo.remaining(999_999), 0);
});

test('answers persist across question navigation and can be changed before finishing', () => {
    const demo = new TryoutDemo();
    demo.start(1000);
    demo.answer(1, 2000);
    demo.navigate(1);
    demo.answer(2, 3000);
    demo.navigate(2);
    demo.answer(1, 4000);
    demo.navigate(0);
    demo.answer(0, 5000);

    assert.deepEqual(demo.answers, [0, 2, 1]);
    assert.equal(demo.finish(), 3);
    demo.answer(3, 6000);
    assert.deepEqual(demo.answers, [0, 2, 1]);
});

test('finishing with wrong or unanswered questions gives only earned points', () => {
    const demo = new TryoutDemo();
    demo.start(1000);
    demo.answer(3, 2000);
    demo.navigate(2);
    demo.answer(1, 3000);

    assert.equal(demo.finish(), 1);
    assert.equal(demo.finish(), 1);
});

test('expired demos reject late answers and keep the previous result', () => {
    const demo = new TryoutDemo();
    demo.start(1000);
    demo.answer(0, 2000);
    demo.navigate(1);
    demo.answer(2, 181_000);

    assert.equal(demo.finished, true);
    assert.deepEqual(demo.answers, [0, null, null]);
    assert.equal(demo.finish(), 1);
});

test('invalid navigation and answer choices cannot corrupt a demo', () => {
    const demo = new TryoutDemo();
    demo.start(1000);
    for (const index of [-1, 3, 0.5, NaN]) demo.navigate(index);
    for (const option of [-1, 4, 0.5, NaN]) demo.answer(option, 2000);

    assert.equal(demo.current, 0);
    assert.deepEqual(demo.answers, [null, null, null]);
    assert.equal(new TryoutDemo().remaining(), 180);
});
