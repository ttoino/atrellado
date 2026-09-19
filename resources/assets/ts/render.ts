const format = (format: string, arg: unknown) =>
    format.replace("{}", String(arg));

const renderMethods: {
    [k: string]: (el: HTMLElement, prop: unknown, ...args: string[]) => unknown;
} = {
    attr: (el, p, ...attrs) =>
        attrs.forEach((a) => el.setAttribute(`data-${a}`, String(p))),
    "class-condition": (el, p, clas = "", flipped = "true") =>
        el.classList.toggle(clas, Boolean(p) === (flipped === "true")),
    "css-var": (el, p, ...vars) =>
        vars.forEach((v) => el.style.setProperty(`--${v}`, String(p))),
    datetime: (el, p) =>
        el instanceof HTMLTimeElement &&
        (typeof p == "string" || typeof p == "number" || p instanceof Date) &&
        (el.dateTime = new Date(p).toString()),
    href: (el, p, fmt = "{}") =>
        el instanceof HTMLAnchorElement && (el.href = format(fmt, p)),
    html: (el, p) => (el.innerHTML = String(p)),
    src: (el, p, fmt = "{}") =>
        el instanceof HTMLImageElement && (el.src = format(fmt, p)),
    text: (el, p) => (el.innerText = String(p)),
    value: (el, p) =>
        (el instanceof HTMLInputElement || el instanceof HTMLTextAreaElement) &&
        (el.value = String(p)),
};

export const render = <T extends object>(el: HTMLElement, data: T) => {
    for (const method in renderMethods) {
        const selector = `[data-render-${method}]`;
        const places = el.querySelectorAll<HTMLElement>(selector);

        const apply = (place: HTMLElement) => {
            const [prop, ...args] = (
                place.getAttribute(`data-render-${method}`) ?? ""
            ).split(",");
            const value = prop
                .split(".")
                .reduce<unknown>(
                    (obj, key) =>
                        (obj as null | Record<string, unknown> | undefined)?.[
                            key
                        ],
                    data,
                );
            if (value !== undefined)
                renderMethods[method](place, value, ...args);
        };

        if (el.matches(selector)) apply(el);
        places.forEach(apply);
    }

    return el;
};

export const renderSingleton = <T extends object>(selector: string) => {
    const el = document.querySelector<HTMLElement>(selector);

    return el && ((data: T) => render(el, data));
};

export const renderTemplate = <T extends object>(selector: string) => {
    const template = document.querySelector<HTMLTemplateElement>(
        `template${selector}`,
    )?.content.firstElementChild;

    return (
        template &&
        ((data: T) => {
            const el = template.cloneNode(true) as HTMLElement;
            return render(el, data);
        })
    );
};

export const renderList = <T extends object>(
    templateSelector: string,
    listSelector: HTMLElement | string,
) => {
    const list =
        typeof listSelector === "string"
            ? document.querySelector<HTMLElement>(listSelector)
            : listSelector;

    const renderItem = renderTemplate(templateSelector);

    return (
        list &&
        renderItem &&
        ((data: Array<T>) => list.replaceChildren(...data.map(renderItem)))
    );
};

renderMethods.list = (el, p, templateSelector) =>
    p instanceof Array && renderList(templateSelector, el)?.(p);

export const appendListItem = <T extends object>(
    templateSelector: string,
    listSelector: string,
    first: boolean = false,
) => {
    const list = document.querySelector<HTMLElement>(listSelector);

    const renderItem = renderTemplate(templateSelector);

    return (
        list &&
        renderItem &&
        ((data: T) =>
            first
                ? list.insertBefore(renderItem(data), list.firstChild)
                : list.appendChild(renderItem(data)))
    );
};

export const appendListItems = <T extends object>(
    templateSelector: string,
    listSelector: string,
) => {
    const list = document.querySelector<HTMLElement>(listSelector);

    const renderItem = renderTemplate(templateSelector);

    console.log(list, renderItem);

    return (
        list &&
        renderItem &&
        ((data: Array<T>) => list.append(...data.map(renderItem)))
    );
};

export const renderMultiple =
    <T extends object>(
        ...fns: Array<((arg: T) => unknown) | null | undefined>
    ) =>
    (arg: T) =>
        fns.map((fn) => fn?.(arg));
