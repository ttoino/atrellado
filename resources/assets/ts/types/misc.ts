export interface Datetime {
    date: string;
    datetime: string;
    diff: string;
    iso: string;
    long_diff: string;
    time: string;
}

export interface Markdown {
    formatted: string;
    raw: string;
}

export interface Paginator<T> {
    data: Array<T>;
    next_cursor?: string;
}
