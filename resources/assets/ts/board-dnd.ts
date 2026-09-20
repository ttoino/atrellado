import Sortable from "sortablejs";

/**
 * Bridge between sortablejs and the board Livewire component. DOM moves are
 * optimistic: the server re-render morphs the canonical order back, so a
 * failed reposition self-heals. Livewire replaces list elements on morph, so
 * instances are rebuilt whenever anything inside the board changes.
 */

let sortables: Sortable[] = [];

function destroyAll() {
    sortables.forEach((s) => s.destroy());
    sortables = [];
}

function initBoardDnd() {
    destroyAll();

    const board = document.querySelector<HTMLElement>("[data-board]");
    if (!board) return;

    sortables.push(
        new Sortable(board, {
            animation: 150,
            draggable: ".task-group[data-task-group-id]",
            handle: ".group-grip",
            onEnd: (e) => {
                const id = e.item.dataset.taskGroupId;
                if (id && e.newIndex !== undefined && e.oldIndex !== e.newIndex)
                    window.Livewire?.dispatch("group-moved", {
                        id: Number(id),
                        position: e.newIndex + 1,
                    });
            },
        }),
    );

    board.querySelectorAll<HTMLElement>(".task-group > ul").forEach((list) => {
        sortables.push(
            new Sortable(list, {
                animation: 150,
                draggable: ".task",
                group: "tasks",
                handle: ".grip",
                onEnd: (e) => {
                    const taskId = e.item.dataset.taskId;
                    const groupId = (e.to as HTMLElement)
                        .closest("[data-task-group-id]")
                        ?.getAttribute("data-task-group-id");

                    if (
                        !taskId ||
                        !groupId ||
                        e.newIndex === undefined ||
                        (e.from === e.to && e.oldIndex === e.newIndex)
                    )
                        return;

                    window.Livewire?.dispatch("task-moved", {
                        group: Number(groupId),
                        id: Number(taskId),
                        position: e.newIndex + 1,
                    });
                },
            }),
        );
    });
}

document.addEventListener("livewire:init", () => {
    initBoardDnd();
    document.addEventListener("livewire:navigated", initBoardDnd);
    window.Livewire?.hook("morph.updated", ({ el }) => {
        if ((el as HTMLElement).closest?.("[data-board]")) initBoardDnd();
    });
});
