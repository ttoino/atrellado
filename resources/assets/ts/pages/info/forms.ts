import { editProject } from "../../api/project";
import { registerEnhancement } from "../../enhancements";
import { ajaxForm } from "../../forms";
import { projectId } from "../project";
import { renderProject } from "./render";

// EDIT PROJECT
registerEnhancement<HTMLFormElement>({
    onattach: (form) =>
        ajaxForm(
            editProject,
            form,
            { id: parseInt(projectId) },
            (project) => {
                renderProject?.(project);
                document
                    .querySelector(".project-info .left")
                    ?.classList.remove("editing");
            },
            () => {},
        ),
    selector: "form#edit-project-form",
});
