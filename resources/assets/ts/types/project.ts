import { Datetime, Markdown } from "./misc";

export interface Project {
    archived: boolean;
    coordinator_id: number;
    creation_date: Datetime;
    description: Markdown;
    edit_date: Datetime;
    id: number;
    name: string;
}
