<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple PHP Unit Test Runner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    .status-passed { color: green; font-weight: bold; }
    .status-failed { color: red; font-weight: bold; }
    .card { margin-bottom: 20px; }
    div#consoleLog { max-height: 488px; }
</style>
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Target URL</h5>
                </div>
                <div class="card-body">
                    <div class="input-group mb-3">
                        <input type="url" id="targetUrl" class="form-control" placeholder="https://example.com" aria-label="Website URL">
                        <button class="btn btn-primary" type="button" onclick="handleUrl()">Set URL</button>
                    </div>
<div class="d-flex flex-wrap gap-2">
    <button class="btn btn-sm btn-info text-white" type="button" onclick="runWebTest('html_elements')">Test HTML Elements</button>
    <button class="btn btn-sm btn-info text-white" type="button" onclick="runWebTest('broken_links')">Test Broken Links</button>
    <button class="btn btn-sm btn-info text-white" type="button" onclick="runWebTest('seo_tags')">Test SEO Tags</button>
    <button class="btn btn-sm btn-info text-white" type="button" onclick="runWebTest('security_headers')">Test Security Headers</button>
    <button class="btn btn-sm btn-info text-white" type="button" onclick="runWebTest('readability')">Test Readability</button>
    <button class="btn btn-sm btn-info text-white" type="button" onclick="runWebTest('image_optimization')">Test Image Optimization</button>
    <button class="btn btn-sm btn-info text-white" type="button" onclick="runWebTest('broken_forms')">Test Broken Forms</button>
    <button class="btn btn-sm btn-info text-white" type="button" onclick="runWebTest('external_scripts')">Test External Scripts</button>
    <button class="btn btn-sm btn-secondary text-white" type="button" onclick="runWebTest('performance')">Test Performance</button>
    <button class="btn btn-sm btn-secondary text-white" type="button" onclick="runWebTest('accessibility')">Test Accessibility</button>
    <button class="btn btn-sm btn-secondary text-white" type="button" onclick="runWebTest('mobile_view')">Test Mobile View</button>
    <button class="btn btn-sm btn-secondary text-white" type="button" onclick="runWebTest('cookies')">Test Cookies</button>
    <button class="btn btn-sm btn-secondary text-white" type="button" onclick="runWebTest('https_redirect')">Test HTTPS Redirect</button>
    <button class="btn btn-sm btn-secondary text-white" type="button" onclick="runWebTest('sitemap')">Test Sitemap</button>
</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Available Tests</h5>
                </div>
                <div class="card-body">
                    <table class="table table-hover" id="testTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Test Name</th>
                                <th>Status</th>
                                <th>Last Result</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data loaded via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-8">
                
                <div class="card-body bg-dark text-white" id="consoleLog" style="overflow-y: auto; font-family: monospace; font-size: 0.85rem;">
                    <div>System ready...</div>
                </div></div>
            </div>
        </div>
    </div>
</div>

<script>
    const apiEndpoint = 'api/api.php';
    let csrfToken = '';

    async function fetchTests() {
        try {
            const response = await fetch(`${apiEndpoint}?action=list`);
            const data = await response.json();
            
            csrfToken = data.csrf_token;
            const tests = data.tests;
            const tbody = document.querySelector('#testTable tbody');
            tbody.innerHTML = '';

            tests.forEach(test => {
                const statusClass = test.status === 'passed' ? 'status-passed' : (test.status ? 'status-failed' : '');
                const statusText = test.status ? test.status.toUpperCase() : '-';
                const lastMsg = test.message || '-';
                
                const row = document.createElement('tr');
                
                const idTd = document.createElement('td');
                idTd.textContent = test.id;
                
                const nameTd = document.createElement('td');
                const nameStrong = document.createElement('strong');
                nameStrong.textContent = test.name;
                const methodBr = document.createElement('br');
                const methodSmall = document.createElement('small');
                methodSmall.className = 'text-muted';
                methodSmall.textContent = test.method_name;
                nameTd.appendChild(nameStrong);
                nameTd.appendChild(methodBr);
                nameTd.appendChild(methodSmall);
                
                const statusTd = document.createElement('td');
                statusTd.className = statusClass;
                statusTd.textContent = statusText;
                
                const msgTd = document.createElement('td');
                const msgSmall = document.createElement('small');
                msgSmall.textContent = lastMsg;
                msgTd.appendChild(msgSmall);
                
                const actionTd = document.createElement('td');
                const btn = document.createElement('button');
                btn.className = 'btn btn-sm btn-success';
                btn.textContent = 'Run';
                btn.onclick = () => runTest(test.id);
                actionTd.appendChild(btn);
                
                row.append(idTd, nameTd, statusTd, msgTd, actionTd);
                tbody.appendChild(row);
            });
        } catch (err) {
            log('Error fetching tests: ' + err.message, 'danger');
        }
    }

    function handleUrl() {
        const url = document.getElementById('targetUrl').value;
        if (!url) {
            log('Please enter a URL', 'danger');
            return;
        }
        try {
            new URL(url);
            log(`Target URL set to: ${url}`, 'passed');
        } catch (e) {
            log('Invalid URL format', 'danger');
        }
    }

    async function runWebTest(testType) {
        const url = document.getElementById('targetUrl').value;
        if (!url) {
            log('Please enter a URL first', 'danger');
            return;
        }

        log(`Running web scan: ${testType}...`);
        const formData = new FormData();
        formData.append('url', url);
        formData.append('test_type', testType);
        formData.append('csrf_token', csrfToken);

        try {
            const response = await fetch(`${apiEndpoint}?action=scan`, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.error) {
                log('Error: ' + result.error, 'danger');
            } else {
                log(`Scan Result: ${result.message}`, result.status);
            }
        } catch (err) {
            log('Error executing scan: ' + err.message, 'danger');
        }
    }

    async function runTest(testId) {
        log(`Running test ID: ${testId}...`);
        const formData = new FormData();
        formData.append('test_id', testId);
        formData.append('csrf_token', csrfToken);

        try {
            const response = await fetch(`${apiEndpoint}?action=run`, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.error) {
                log('Error: ' + result.error, 'danger');
            } else {
                log(`Test result: ${result.status.toUpperCase()} - ${result.message}`, result.status);
                fetchTests();
            }
        } catch (err) {
            log('Error executing test: ' + err.message, 'danger');
        }
    }

    function log(msg, type = '') {
        const consoleLog = document.getElementById('consoleLog');
        const div = document.createElement('div');
        div.className = type === 'danger' ? 'text-danger' : (type === 'passed' ? 'text-success' : '');
        div.textContent = `> ${msg}`;
        consoleLog.appendChild(div);
        consoleLog.scrollTop = consoleLog.scrollHeight;
    }

    document.addEventListener('DOMContentLoaded', fetchTests);
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<footer class="mt-5 py-3 bg-white text-center border-top">
    <div class="container">
        <p class="mb-1">&copy; 2026 Unit Testing App</p>
        <p class="mb-0">Author: <a href="https://github.com/rowerowydaniel-alt/Unit_Testing_App" target="_blank" class="text-decoration-none">rowerowydaniel-alt</a></p>
    </div>
</footer>
</body>
</html>
