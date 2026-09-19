import { Datetime, Markdown } from "./misc";
import { User } from "./user";

export interface ThreadComment {
    author?: User;
    author_id: number;
    content: Markdown;
    creation_date: Datetime;
    edit_date: Datetime;
    id: number;

    thread_id: number;
}
