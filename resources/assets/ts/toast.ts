import { Toast } from "bootstrap";

import { registerEnhancement } from "./enhancements";
import { appendListItem } from "./render";

export const renderToast = appendListItem<{ text: string }>(
    "#toast-template",
    ".toast-container",
);

registerEnhancement({
    onattach: (el) => {
        Toast.getOrCreateInstance(el).show();
        el.addEventListener("hidden.bs.toast", () => el.remove());
    },
    selector: ".toast",
});
