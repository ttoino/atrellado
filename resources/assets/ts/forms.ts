import { APIError, apiFetch } from "./api";
import { renderToast } from "./toast";

export const ajaxForm = <K, P>(
    fn: (param: P) => ReturnType<typeof apiFetch<K>>,
    form: HTMLFormElement,
    constantData: Partial<P>,
    ok: (data: K) => unknown,
    notOk: (error?: APIError) => unknown,
) => {
    form.addEventListener("submit", async (e) => {
        e.preventDefault();
        const data: Record<
            string,
            Array<FormDataEntryValue> | FormDataEntryValue | null
        > = {};
        const formData = new FormData(form);

        for (const key of formData.keys()) {
            data[key.replace("[]", "")] = key.endsWith("[]")
                ? formData.getAll(key)
                : formData.get(key);
        }

        try {
            const payload =
                constantData instanceof Object
                    ? { ...constantData, ...data }
                    : constantData;
            const response = await fn(payload as P);

            if (response.ok) {
                form.reset();
                ok(await response.json());
            } else {
                const error = await response.json();
                if (error?.message) renderToast?.({ text: error.message });
                notOk(error);
            }
        } catch {
            notOk();
        }
    });
};
