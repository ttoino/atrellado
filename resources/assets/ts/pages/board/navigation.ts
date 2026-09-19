import { Offcanvas } from "bootstrap";

import { getTask } from "../../api/task";
import { registerEnhancement } from "../../enhancements";
import { ajaxNavigation, navigation } from "../../navigation";
import { Task } from "../../types/task";
import { projectId } from "../project";
import { renderTask, renderTaskComments } from "./render";

const taskOffcanvasEl = document.querySelector("#task-offcanvas");
const taskOffcanvas =
    taskOffcanvasEl && Offcanvas.getOrCreateInstance(taskOffcanvasEl);

export const showBoard = navigation(
    "project.board",
    `/project/${projectId}/board`,
    () => taskOffcanvas?.hide(),
);

taskOffcanvasEl?.addEventListener("hide.bs.offcanvas", () => {
    if (history.state?.name != "project.board") showBoard();
});

const showTask = ajaxNavigation(
    "project.task",
    getTask,
    (task: Task) => {
        taskOffcanvas?.show();

        document.querySelector("#task")?.classList.remove("editing");
        renderTask?.(task);
        if (task.comments) renderTaskComments(task.comments);

        taskOffcanvasEl?.classList.remove("loading");
    },
    () => {
        taskOffcanvas?.show();
        taskOffcanvasEl?.classList.remove("loading");
    },
    () => {
        taskOffcanvas?.show();
        taskOffcanvasEl?.classList.add("loading");
    },
);

registerEnhancement({
    onattach: (task) => {
        const taskId = task.dataset.taskId;
        if (!taskId) return;

        const a = task.querySelector<HTMLAnchorElement>("a.stretched-link");

        a?.addEventListener("click", (e) => {
            e.preventDefault();

            showTask(`/project/${projectId}/task/${taskId}`, taskId ?? "");
        });
    },
    selector: ".task",
});
