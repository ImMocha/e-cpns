import './lontar/icons.js';
import './lontar/testimonial-carousel.js';
import './lontar/charts.js';
import './lontar/app.js';
import './lontar/auto-slug.js';
import './lontar/theme.js';
import './lontar/local-time.js';
import './lontar/legal-dialogs.js';
import './lontar/navbar-search.js';
import './lontar/content-workspace.js';
import './lontar/article-editor.js';
import './lontar/question-editor.js';
import './lontar/exam-question-manager.js';
import './lontar/quiz.js';
import './lontar/pdf-reader.js';

if (document.querySelector('[data-tryout-demo]')) {
    import('./lontar/tryout-demo.js');
}

if (document.querySelector('[data-typography-settings]')) {
    import('./lontar/typography-settings.js');
}

if (document.querySelector('[data-feature-help]')) {
    import('./lontar/feature-help.js');
}

if (document.querySelector('[data-package-picker]')) {
    import('./lontar/package-content-picker.js');
}

if (document.querySelector('[data-package-carousel]')) {
    import('./lontar/package-carousel.js');
}
