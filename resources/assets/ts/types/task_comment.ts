import { Datetime, Markdown } from "./misc";
import { Task } from "./task";
import { User } from "./user";

export interface TaskComment {
    author?: User;
    author_id: number;
    content: Markdown;
    creation_date: Datetime;

    edit_date: Datetime;
    id: number;

    task?: Task;
    task_id: number;
}
