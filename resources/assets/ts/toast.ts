import { Toast } from "bootstrap";

import { registerEnhancement } from "./enhancements";

export const renderToast = ({ text }: { text: string }) => {
    const container = document.querySelector(".toast-container");
    if (!container) return;

    const toast = document.createElement("div");
    toast.className = "toast d-flex align-items-center";
    toast.setAttribute("role", "alert");
    toast.setAttribute("aria-live", "assertive");
    toast.setAttribute("aria-atomic", "true");

    const body = document.createElement("div");
    body.className = "toast-body";
    body.innerText = text;

    const close = document.createElement("button");
    close.type = "button";
    close.className = "btn-close m-3";
    close.setAttribute("data-bs-dismiss", "toast");
    close.setAttribute("aria-label", "Close");

    toast.append(body, close);
    container.append(toast);
};

registerEnhancement({
    onattach: (el) => {
        Toast.getOrCreateInstance(el).show();
        el.addEventListener("hidden.bs.toast", () => el.remove());
    },
    selector: ".toast",
});
