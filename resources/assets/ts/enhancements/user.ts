import { tryRequest } from "../api";
import { blockUser, deleteUser, unblockUser } from "../api/user";
import { registerEnhancement } from "../enhancements";

registerEnhancement<HTMLElement>({
    onattach: (el) => {
        const list = el.parentElement;
        const userId = el.dataset.userId;

        if (!userId) return;

        const deleteUserButton =
            el.querySelector<HTMLButtonElement>("button.delete-user");
        deleteUserButton?.addEventListener("click", async () => {
            const result = await tryRequest(deleteUser, undefined, userId);

            if (result) {
                el.remove();
                if (list?.childElementCount == 0) window.location.reload();
            }
        });

        const blockUserButton =
            el.querySelector<HTMLButtonElement>("button.block-user");
        blockUserButton?.addEventListener("click", async () => {
            const result = await tryRequest(blockUser, undefined, userId);

            if (result) {
                window.location.reload();
            }
        });

        const unblockUserButton = el.querySelector<HTMLButtonElement>(
            "button.unblock-user",
        );
        unblockUserButton?.addEventListener("click", async () => {
            const result = await tryRequest(unblockUser, undefined, userId);

            if (result) {
                window.location.reload();
            }
        });
    },
    selector: "[data-user-id]",
});

// TODO: Move this to user page script
registerEnhancement<HTMLElement>({
    onattach: (el) => {
        const userId = /user\/(\d+)/.exec(location.pathname)?.[0][1];

        if (!userId) return;

        const deleteUserButton =
            el.querySelector<HTMLButtonElement>("button.delete-user");

        deleteUserButton?.addEventListener("click", async () => {
            const result = await tryRequest(deleteUser, undefined, userId);

            if (result) window.location.reload();
        });
    },
    selector: "main",
});
