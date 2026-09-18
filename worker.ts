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
		// The wasm heap high-water is monotonic within an isolate; recycle
		// the PHP instance before fragmentation can reach the 128 MiB cap.
		maxRequestsPerInstance: 50,
		bindings: {
			DB:      "d1",
			FILES:   "r2",
			CACHE:   "kv",
			EMAIL:   "send_email",
			APP_ENV: "var",
		},
		staticRoutes: [
			{pathPrefix: "/storage/", from: "FILES", stripPrefix: true},
		],
	}),
};
