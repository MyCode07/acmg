import "./parts/popup.js";
import "./parts/menu.js";
import "./parts/tabs.js";
import "./parts/sliders.js";
import "./parts/select.js";
import "./parts/filter.js";

import { maskInputs } from "./static/inputmask.js";
import { stickyHeader } from "./parts/header.js";
import { Fancybox } from "@fancyapps/ui";
import { replaceDomElements } from "./static/replace.js";

replaceDomElements()
maskInputs('+7 (999) 999-99-99', '._mask-phone')
stickyHeader()
Fancybox.bind("[data-fancybox]", {
});

document.addEventListener('click', function (e) {
    let targetEl = e.target;
    if (targetEl.classList.contains('pages-close')) {
        document.querySelector('.pages').classList.toggle('_hide');
    }
})