export interface Enhancement<E extends HTMLElement> {
    onattach?(e: Enhanced<E>): unknown;
    ondettach?(e: Enhanced<E>): unknown;
    selector: string;
}

type Enhanced<E extends HTMLElement> = {
    enhancements: Set<Enhancement<E>>;
} & E;

export const enhancements = new Set<Enhancement<HTMLElement>>();

const addEnhancement =
    <E extends HTMLElement>(enhancement: Enhancement<E>) =>
    (e: Enhanced<E>) => {
        e.enhancements ??= new Set<Enhancement<E>>();

        if (e.enhancements.has(enhancement)) return;

        e.enhancements.add(enhancement);

        e.dataset.enhanced = "true";

        enhancement.onattach?.(e);
    };

const mutationObserver = new MutationObserver((records) => {
    for (const record of records) {
        if (record.type != "childList") continue;

        for (const enhancement of enhancements) {
            const elements = document.querySelectorAll<Enhanced<HTMLElement>>(
                enhancement.selector,
            );
            elements.forEach(addEnhancement(enhancement));
        }
    }
});
mutationObserver.observe(document, {
    childList: true,
    subtree: true,
});

export const registerEnhancement = <E extends HTMLElement>(
    enhancement: Enhancement<E>,
) => {
    enhancements.add(enhancement);

    const elements = document.querySelectorAll<Enhanced<E>>(
        enhancement.selector,
    );

    elements.forEach(addEnhancement(enhancement));
};
