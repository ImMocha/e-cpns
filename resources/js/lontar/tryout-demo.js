export const demoQuestions = [
    {
        category: 'TWK',
        question: 'Menghormati kebebasan orang lain dalam menjalankan ibadah merupakan pengamalan sila ke berapa?',
        options: ['Sila pertama', 'Sila kedua', 'Sila ketiga', 'Sila keempat'],
        correct: 0,
        explanation: 'Sila Ketuhanan Yang Maha Esa mengajarkan sikap saling menghormati kebebasan beragama dan beribadah.',
    },
    {
        category: 'TIU',
        question: 'Tentukan angka berikutnya dalam deret: 3, 6, 12, 24, …',
        options: ['30', '36', '48', '54'],
        correct: 2,
        explanation: 'Setiap angka dikalikan dua. Angka setelah 24 adalah 24 × 2 = 48.',
    },
    {
        category: 'TKP',
        question: 'Rekan kerja kesulitan menyelesaikan tugas, sementara tugas Anda sudah selesai. Apa tindakan yang paling tepat?',
        options: ['Menunggu sampai diminta atasan', 'Menawarkan bantuan sesuai kemampuan', 'Mengambil alih tanpa berdiskusi', 'Membiarkannya belajar sendiri'],
        correct: 1,
        explanation: 'Menawarkan bantuan menunjukkan kerja sama dan kepedulian, sambil tetap menghargai tanggung jawab rekan.',
    },
];

export class TryoutDemo {
    constructor() {
        this.answers = Array(demoQuestions.length).fill(null);
        this.current = 0;
        this.deadline = null;
        this.finished = false;
    }

    start(now = Date.now()) {
        if (this.deadline === null && !this.finished) this.deadline = now + 180_000;
    }

    remaining(now = Date.now()) {
        return this.deadline === null ? 180 : Math.max(0, Math.ceil((this.deadline - now) / 1000));
    }

    answer(option, now = Date.now()) {
        if (this.deadline === null || this.finished) return;
        if (this.remaining(now) === 0) {
            this.finish();
            return;
        }
        if (Number.isInteger(option) && option >= 0 && option < demoQuestions[this.current].options.length) {
            this.answers[this.current] = option;
        }
    }

    navigate(index) {
        if (Number.isInteger(index) && index >= 0 && index < demoQuestions.length) this.current = index;
    }

    finish() {
        this.finished = true;
        return demoQuestions.reduce((score, question, index) => score + Number(this.answers[index] === question.correct), 0);
    }
}

export function initTryoutDemo(root) {
    let demo = new TryoutDemo();
    let interval;
    const find = name => root.querySelector(`[data-demo-${name}]`);
    const numbers = [...root.querySelectorAll('[data-demo-number]')];
    const status = find('status');

    const renderTimer = () => {
        const seconds = demo.remaining();
        find('timer').textContent = `${String(Math.floor(seconds / 60)).padStart(2, '0')}:${String(seconds % 60).padStart(2, '0')}`;
    };

    const renderQuestion = (focus = false) => {
        const question = demoQuestions[demo.current];
        find('category').textContent = question.category;
        find('progress').textContent = `${demo.answers.filter(answer => answer !== null).length} / 3 terjawab`;
        find('question').textContent = `${demo.current + 1}. ${question.question}`;
        find('options').replaceChildren(...question.options.map((option, index) => {
            const label = document.createElement('label');
            label.className = 'tryout-demo__option';
            const input = document.createElement('input');
            input.type = 'radio';
            input.name = 'demo-answer';
            input.value = String(index);
            input.checked = demo.answers[demo.current] === index;
            input.addEventListener('change', () => {
                demo.answer(index);
                if (demo.finished) {
                    showResult(true);
                    return;
                }
                updateNavigation();
                status.textContent = `Jawaban soal ${demo.current + 1} dipilih. Kamu bisa menggantinya sebelum selesai.`;
            });
            const text = document.createElement('span');
            text.textContent = `${String.fromCharCode(65 + index)}. ${option}`;
            label.append(input, text);
            return label;
        }));
        updateNavigation();
        if (focus) find('question').focus();
    };

    const updateNavigation = () => {
        find('progress').textContent = `${demo.answers.filter(answer => answer !== null).length} / 3 terjawab`;
        numbers.forEach((button, index) => {
            button.classList.toggle('is-answered', demo.answers[index] !== null);
            button.setAttribute('aria-label', `Soal ${index + 1}, ${demo.answers[index] !== null ? 'sudah dijawab' : 'belum dijawab'}`);
            if (index === demo.current) button.setAttribute('aria-current', 'step');
            else button.removeAttribute('aria-current');
        });
        find('previous').disabled = demo.current === 0;
        find('next').disabled = demo.current === demoQuestions.length - 1;
    };

    const showResult = (expired = false) => {
        clearInterval(interval);
        const score = demo.finish();
        renderTimer();
        find('workspace').hidden = true;
        find('result').hidden = false;
        find('score').textContent = `${score} dari 3 jawaban tepat`;
        find('summary').textContent = 'Penilaian demo memakai satu jawaban terbaik per soal. Tryout lengkap mengikuti penilaian tiap bidang, termasuk bobot TKP.';
        find('review').replaceChildren(...demoQuestions.map((question, index) => {
            const item = document.createElement('div');
            const title = document.createElement('strong');
            const answer = demo.answers[index];
            title.textContent = `${question.category} · ${answer === null ? 'Belum dijawab' : answer === question.correct ? 'Tepat' : 'Belum tepat'}`;
            const description = document.createElement('p');
            description.textContent = `Jawaban: ${question.options[question.correct]}. ${question.explanation}`;
            item.append(title, description);
            return item;
        }));
        status.textContent = expired ? 'Waktu habis. Demo otomatis diselesaikan.' : 'Demo selesai. Hasil ini tidak masuk riwayat atau leaderboard.';
        find('score').focus();
    };

    find('start').addEventListener('click', () => {
        demo.start();
        find('intro').hidden = true;
        find('workspace').hidden = false;
        status.textContent = 'Demo dimulai. Pilih jawaban sebelum waktu habis.';
        renderQuestion(true);
        interval = setInterval(() => {
            renderTimer();
            if (demo.remaining() === 0) showResult(true);
        }, 1000);
    });
    const navigate = index => {
        if (demo.remaining() === 0) return showResult(true);
        demo.navigate(index);
        renderQuestion(true);
    };
    numbers.forEach((button, index) => button.addEventListener('click', () => navigate(index)));
    find('previous').addEventListener('click', () => navigate(demo.current - 1));
    find('next').addEventListener('click', () => navigate(demo.current + 1));
    find('finish').addEventListener('click', () => showResult(demo.remaining() === 0));
    find('reset').addEventListener('click', () => {
        clearInterval(interval);
        demo = new TryoutDemo();
        find('intro').hidden = false;
        find('workspace').hidden = true;
        find('result').hidden = true;
        status.textContent = 'Demo siap diulang. Tekan Mulai demo untuk memulai timer.';
        renderTimer();
        find('start').focus();
    });
}

if (typeof document !== 'undefined') {
    document.querySelectorAll('[data-tryout-demo]').forEach(initTryoutDemo);
}
