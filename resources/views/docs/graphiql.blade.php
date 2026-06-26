<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data Karyawan GraphiQL</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f7f8fa;
            --panel: #ffffff;
            --line: #d7dce2;
            --text: #18202a;
            --muted: #667085;
            --accent: #0969da;
            --accent-dark: #0757b8;
            --ok: #16794f;
            --error: #b42318;
            --code: #101828;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: var(--bg);
            color: var(--text);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .graphiql-shell {
            display: grid;
            grid-template-rows: 58px 1fr;
            min-height: 100vh;
        }

        .graphiql-bar {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 0 20px;
            background: var(--panel);
            border-bottom: 1px solid var(--line);
        }

        .graphiql-title {
            font-size: 17px;
            font-weight: 700;
            line-height: 1;
        }

        .graphiql-endpoint {
            color: var(--muted);
            font-family: Consolas, "Liberation Mono", monospace;
            font-size: 13px;
        }

        .graphiql-status {
            margin-left: auto;
            color: var(--muted);
            font-size: 13px;
            white-space: nowrap;
        }

        .graphiql-status[data-state="success"] {
            color: var(--ok);
        }

        .graphiql-status[data-state="error"] {
            color: var(--error);
        }

        .graphiql-main {
            display: grid;
            grid-template-columns: minmax(320px, 1fr) minmax(320px, 1fr);
            min-height: 0;
        }

        .graphiql-pane {
            display: grid;
            grid-template-rows: 44px 1fr;
            min-width: 0;
            min-height: 0;
            background: var(--panel);
        }

        .graphiql-pane + .graphiql-pane {
            border-left: 1px solid var(--line);
        }

        .pane-head {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 14px;
            border-bottom: 1px solid var(--line);
            font-size: 13px;
            font-weight: 700;
        }

        .run-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            height: 32px;
            padding: 0 13px;
            margin-left: auto;
            border: 0;
            border-radius: 6px;
            background: var(--accent);
            color: #ffffff;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
        }

        .run-button:hover {
            background: var(--accent-dark);
        }

        .run-button:disabled {
            cursor: wait;
            opacity: .65;
        }

        .run-icon {
            width: 0;
            height: 0;
            border-top: 6px solid transparent;
            border-bottom: 6px solid transparent;
            border-left: 9px solid currentColor;
        }

        .query-editor,
        .result-view {
            width: 100%;
            height: 100%;
            min-height: 0;
            margin: 0;
            padding: 18px;
            border: 0;
            outline: 0;
            resize: none;
            color: var(--code);
            background: var(--panel);
            font: 14px/1.55 Consolas, "Liberation Mono", "Courier New", monospace;
            tab-size: 2;
        }

        .result-view {
            overflow: auto;
            white-space: pre-wrap;
        }

        @media (max-width: 780px) {
            .graphiql-main {
                grid-template-columns: 1fr;
                grid-template-rows: 1fr 1fr;
            }

            .graphiql-pane + .graphiql-pane {
                border-left: 0;
                border-top: 1px solid var(--line);
            }

            .graphiql-endpoint {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="graphiql-shell">
        <header class="graphiql-bar">
            <div class="graphiql-title">GraphiQL</div>
            <div class="graphiql-endpoint">POST /graphql</div>
            <div class="graphiql-status" id="queryStatus" data-state="idle">Ready</div>
        </header>

        <main class="graphiql-main">
            <section class="graphiql-pane" aria-label="GraphQL query editor">
                <div class="pane-head">
                    Query
                    <button class="run-button" id="runQuery" type="button">
                        <span class="run-icon" aria-hidden="true"></span>
                        Run
                    </button>
                </div>
                <textarea class="query-editor" id="queryEditor" spellcheck="false">query {
  employees {
    id
    employee_id
    name
    department
    status
  }
}</textarea>
            </section>

            <section class="graphiql-pane" aria-label="GraphQL query result">
                <div class="pane-head">Result</div>
                <pre class="result-view" id="queryResult">{}</pre>
            </section>
        </main>
    </div>

    <script>
        const endpoint = @json(url('/graphql'));
        const apiKey = @json(config('iae.api_key'));
        const editor = document.getElementById('queryEditor');
        const result = document.getElementById('queryResult');
        const runButton = document.getElementById('runQuery');
        const status = document.getElementById('queryStatus');

        function setStatus(message, state = 'idle') {
            status.textContent = message;
            status.dataset.state = state;
        }

        async function runQuery() {
            runButton.disabled = true;
            setStatus('Running...');

            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-IAE-KEY': apiKey,
                    },
                    body: JSON.stringify({ query: editor.value }),
                });

                const payload = await response.json();
                result.textContent = JSON.stringify(payload, null, 2);
                setStatus(response.ok && !payload.errors ? 'Success' : `HTTP ${response.status}`, response.ok && !payload.errors ? 'success' : 'error');
            } catch (error) {
                result.textContent = JSON.stringify({
                    status: 'error',
                    message: error.message,
                }, null, 2);
                setStatus('Request failed', 'error');
            } finally {
                runButton.disabled = false;
            }
        }

        runButton.addEventListener('click', runQuery);
        editor.addEventListener('keydown', (event) => {
            if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
                event.preventDefault();
                runQuery();
            }
        });
    </script>
</body>
</html>
