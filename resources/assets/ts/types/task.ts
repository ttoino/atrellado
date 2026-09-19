import { Datetime, Markdown, Paginator } from "./misc";
import { Tag } from "./tag";
import { TaskComment } from "./task_comment";
import { User } from "./user";

export interface Task {
    assignees?: Array<User>;
    comments?: Paginator<TaskComment>;
    completed: boolean;
    creation_date: Datetime;
    creator_id: number;
    description: Markdown;
    edit_date: Datetime;
    id: number;
    name: string;

    position: number;
    tags?: Array<Tag>;
    task_group_id: number;
}
