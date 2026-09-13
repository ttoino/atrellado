// Worker entrypoint. The D1 binding backs Laravel's custom `d1`
// database driver; APP_ENV surfaces to PHP as `$env->APP_ENV`.

import {createPhpHandler} from "workers-php";

export default {
	fetch: createPhpHandler({
		docroot: "public",
		entrypoint: "index.php",
		displayErrors: false,
		bindings: {
			DB:      "d1",
			APP_ENV: "var",
		},
	}),
};
