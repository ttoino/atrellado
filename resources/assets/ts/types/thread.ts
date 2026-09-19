import { Datetime, Markdown, Paginator } from "./misc";
import { ThreadComment } from "./thread_comment";
import { User } from "./user";

export interface Thread {
    author?: User;
    author_id: number;
    comments?: Paginator<ThreadComment>;
    content: Markdown;
    creation_date: Datetime;
    edit_date: Datetime;
    id: number;

    project_id: number;
    title: string;
}
