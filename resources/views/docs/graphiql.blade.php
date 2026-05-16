<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data Karyawan GraphiQL</title>
    <link rel="stylesheet" href="https://unpkg.com/graphiql/graphiql.min.css">
    <style>
        body { height: 100vh; margin: 0; }
        #graphiql { height: 100vh; }
    </style>
</head>
<body>
    <div id="graphiql"></div>
    <script crossorigin src="https://unpkg.com/react/umd/react.production.min.js"></script>
    <script crossorigin src="https://unpkg.com/react-dom/umd/react-dom.production.min.js"></script>
    <script crossorigin src="https://unpkg.com/graphiql/graphiql.min.js"></script>
    <script>
        const fetcher = GraphiQL.createFetcher({
            url: '/graphql',
            headers: { 'X-IAE-KEY': '102022400197' }
        });

        ReactDOM.createRoot(document.getElementById('graphiql')).render(
            React.createElement(GraphiQL, {
                fetcher,
                defaultQuery: `query {\n  employees {\n    employee_id\n    name\n    department\n    status\n  }\n}`
            })
        );
    </script>
</body>
</html>
