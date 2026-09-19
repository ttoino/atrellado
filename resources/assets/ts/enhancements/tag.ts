import { registerEnhancement } from ".";
import { tryRequest } from "../api";
import { deleteTag, editTag, newTag } from "../api/tag";
import { ajaxForm } from "../forms";
import { projectId } from "../pages/project";
import { render } from "../render";

registerEnhancement({
    onattach: (el) => {
        const tagId = el.dataset.tagId!;
        const list = el.parentElement;

        const deleteTagButton =
            el.querySelector<HTMLButtonElement>("button.delete-tag");
        deleteTagButton?.addEventListener("click", async () => {
            const result = await tryRequest(deleteTag, undefined, tagId);

            if (result) {
                el.remove();
                if (list?.childElementCount == 0) window.location.reload();
            }
        });

        const editTagButton =
            el.querySelector<HTMLButtonElement>("button.edit-tag");
        editTagButton?.addEventListener("click", async () => {
            el.classList.add("editing");
        });

        const editTagForm =
            el.querySelector<HTMLFormElement>("form.edit-tag-form");
        if (editTagForm)
            ajaxForm(
                editTag,
                editTagForm,
                { id: parseInt(tagId) },
                (tag) => {
                    render(el, tag);
                    el.classList.remove("editing");
                },
                (e) => {},
            );
    },
    selector: "[data-tag-id]",
});

registerEnhancement<HTMLFormElement>({
    onattach: (el) =>
        ajaxForm(
            newTag,
            el,
            { project_id: parseInt(projectId) },
            (tag) => {
                window.location.reload();
            },
            (e) => {},
        ),
    selector: "form.new-tag-form",
});
