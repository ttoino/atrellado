import { Tooltip } from "bootstrap";

import { registerEnhancement } from ".";

registerEnhancement<HTMLElement>({
    onattach: (e) => {
        new Tooltip(e);
    },
    selector: '[data-bs-toggle="tooltip"]',
});
