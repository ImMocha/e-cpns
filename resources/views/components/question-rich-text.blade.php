@props(['name', 'label', 'value' => ''])
@php($value = is_string($value) ? $value : '')
<div class="question-rich-text" data-question-rich-text data-name="{{ $name }}">
    <label class="label" for="{{ $name }}" id="{{ $name }}-label">{{ $label }}</label>
    <div class="article-toolbar" role="toolbar" aria-label="Format {{ strtolower($label) }}" hidden data-rich-toolbar>
        <button type="button" data-rich-command="bold" title="Bold (Ctrl+B)" aria-label="Bold {{ strtolower($label) }}"><strong>B</strong></button>
        <button type="button" data-rich-command="italic" title="Italic (Ctrl+I)" aria-label="Italic {{ strtolower($label) }}"><em>I</em></button>
        <button type="button" data-rich-command="underline" title="Underline (Ctrl+U)" aria-label="Underline {{ strtolower($label) }}"><u>U</u></button>
        <button type="button" data-rich-command="insertUnorderedList" aria-label="Daftar poin {{ strtolower($label) }}">• ≡</button>
        <button type="button" data-rich-command="insertOrderedList" aria-label="Daftar angka {{ strtolower($label) }}">1.</button>
        <button type="button" data-rich-command="undo" aria-label="Undo {{ strtolower($label) }}">↶</button>
        <button type="button" data-rich-command="redo" aria-label="Redo {{ strtolower($label) }}">↷</button>
    </div>
    <textarea class="textarea" id="{{ $name }}" name="{{ $name }}" rows="6" maxlength="50000" data-rich-source>{{ $value }}</textarea>
    <div class="article-prose question-editable" contenteditable="true" role="textbox" aria-multiline="true" aria-labelledby="{{ $name }}-label" hidden data-rich-content></div>
</div>

