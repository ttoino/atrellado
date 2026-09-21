import Sortable, { SortableEvent } from "sortablejs";

import { tryRequest } from "../../api";
import { repositionTask } from "../../api/task";
import { repositionTaskGroup } from "../../api/task_group";
import { registerEnhancement } from "../../enhancements";

const undo = (e: SortableEvent) => {
    e.item.remove();
    e.from.insertBefore(
        e.item,
        e.oldIndex === undefined ? null : e.from.children[e.oldIndex],
    );
};

registerEnhancement({
    onattach: (taskGroupsContainer) => {
        new Sortable(taskGroupsContainer, {
            animation: 150,
            draggable: ".task-group[data-task-group-id]",
            easing: "ease-in-out",
            group: "taskGroups",
            handle: ".task-group > header > .grip",

            onEnd: async (e) => {
                const taskGroupId = e.item.dataset.taskGroupId;

                if (!taskGroupId) return;

                const newPosition = ((e.newIndex ?? 0) + 1).toString();

                const result = await tryRequest(
                    repositionTaskGroup,
                    undefined,
                    taskGroupId,
                    newPosition,
                );

                if (result === null) undo(e);
            },
        });
    },
    selector: ".project-board",
});

const onTaskMove = async (e: SortableEvent) => {
    const taskId = e.item.dataset.taskId;
    if (!taskId) return;

    const taskGroup = e.to.parentElement?.dataset.taskGroupId;
    if (!taskGroup) return;

    const newPosition = ((e.newIndex ?? 0) + 1).toString();

    const result = await tryRequest(
        repositionTask,
        undefined,
        taskId,
        taskGroup,
        newPosition,
    );

    if (result === null) undo(e);
};

registerEnhancement({
    onattach: (group) => {
        new Sortable(group, {
            animation: 150,
            easing: "cubic-bezier(1, 0, 0, 1)",
            group: "tasks",
            handle: ".grip",
            onEnd: onTaskMove,
        });
    },
    selector: ".task-group > ul",
});
