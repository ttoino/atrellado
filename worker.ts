// Worker entrypoint. The D1 binding backs Laravel's custom `d1`
// database driver; FILES backs the `r2` filesystem disk and serves
// uploaded files under /storage. APP_ENV surfaces to PHP as
// `$env->APP_ENV`.

import {createPhpHandler} from "workers-php";

export default {
	fetch: createPhpHandler({
		docroot: "public",
		entrypoint: "index.php",
		displayErrors: false,
		bindings: {
			DB:      "d1",
			FILES:   "r2",
			EMAIL:   "send_email",
			APP_ENV: "var",
		},
		staticRoutes: [
			{pathPrefix: "/storage/", from: "FILES", stripPrefix: true},
		],
	}),
};
