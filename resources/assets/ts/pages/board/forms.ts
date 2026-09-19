import { editTask, newTask } from "../../api/task";
import { editTaskComment, newTaskComment } from "../../api/task_comment";
import { editTaskGroup, newTaskGroup } from "../../api/task_group";
import { registerEnhancement } from "../../enhancements";
import { ajaxForm } from "../../forms";
import { render } from "../../render";
import { projectId } from "../project";
import {
    appendTaskCard,
    appendTaskComment,
    appendTaskGroup,
    renderTask,
    renderTaskCard,
    renderTaskComment,
} from "./render";

// NEW TASK GROUP
registerEnhancement<HTMLFormElement>({
    onattach: (form) =>
        ajaxForm(
            newTaskGroup,
            form,
            { project_id: parseInt(projectId) },
            (group) => appendTaskGroup(group),
            (error) => {},
        ),
    selector: "form#new-task-group-form",
});

// NEW TASK COMMENT
registerEnhancement<HTMLFormElement>({
    onattach: (form) =>
        ajaxForm(
            newTaskComment,
            form,
            {},
            (taskComment) => appendTaskComment?.(taskComment),
            (error) => {},
        ),
    selector: "form#new-comment-form",
});

// NEW TASK (ADVANCED)
registerEnhancement<HTMLFormElement>({
    onattach: (form) =>
        ajaxForm(
            newTask,
            form,
            {},
            (task) =>
                appendTaskCard(
                    `.task-group[data-task-group-id="${task.task_group_id}"] > ul`,
                )?.(task),
            (error) => {},
        ),
    selector: "form#new-task-form",
});

// EDIT TASK
registerEnhancement<HTMLFormElement>({
    onattach: (form) =>
        ajaxForm(
            editTask,
            form,
            {},
            (task) => {
                renderTask?.(task);
                renderTaskCard(task);
                document.querySelector("#task")?.classList.remove("editing");
            },
            (error) => {},
        ),
    selector: "form#edit-task-form",
});

// EDIT TASK COMMENT
registerEnhancement<HTMLFormElement>({
    onattach: (form) =>
        ajaxForm(
            editTaskComment,
            form,
            {},
            (taskComment) => {
                renderTaskComment?.(taskComment);
                document
                    .querySelector(
                        `.task-comment[data-task-comment-id="${taskComment.id}"]`,
                    )
                    ?.classList.remove("editing");
            },
            (error) => {},
        ),
    selector: "form.edit-task-comment-form",
});

// NEW TASK, EDIT TASK GROUP
registerEnhancement<HTMLElement>({
    onattach: (el) => {
        const taskGroupId = parseInt(el.dataset.taskGroupId!);
        console.log(taskGroupId);

        const appendTask = appendTaskCard(
            `.task-group[data-task-group-id="${taskGroupId}"] > ul`,
        );
        const createTaskForm =
            el.querySelector<HTMLFormElement>("form.new-task-form");
        createTaskForm &&
            ajaxForm(
                newTask,
                createTaskForm,
                { task_group_id: taskGroupId },
                (task) => {
                    appendTask?.(task);
                },
                (error) => {},
            );

        const editGroupForm = el.querySelector<HTMLFormElement>(
            "form.edit-task-group-form",
        );
        editGroupForm &&
            ajaxForm(
                editTaskGroup,
                editGroupForm,
                { id: taskGroupId },
                (group) => {
                    render(editGroupForm, group);
                },
                (error) => {},
            );
    },
    selector: ".task-group[data-task-group-id]",
});
