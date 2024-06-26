<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Service</title>
    <link rel="stylesheet" href="public/styles.css">
</head>
<body>

<div id="container">

    <div>
        <h2>Dashboard</h2>

        <div id="id"></div>

        <div id="data"></div>

        <div id="buttons">
            <input type="button" id="action-stop-server" name="stop-server" value="Stop" disabled/>
            <input type="button" id="action-start-server" name="start-server" value="Start" disabled/>
        </div>

        <div id="website-link">
            >>><a href="http://localhost:8181" target="_blank">Http Server Link</a><<<
        </div>
    </div>

    <hr/>

    <div id="settings">
        <h3>Settings</h3>
        <div class="row">
            <label for="keep-alive">Keep Alive:</label>
            <input type="checkbox" id="keep-alive" name="keep-alive"/>
        </div>
    </div>

</div>

<script>
    // -----------------
    // State
    // -----------------

    let httpServerStatus = false;

    // -----------------
    // DOM Elements
    // -----------------

    const startButton = document.getElementById('action-start-server')
    const stopButton = document.getElementById('action-stop-server')
    const websiteLink = document.getElementById('website-link')
    const infoBox = document.getElementById('data')
    const idBox = document.getElementById('id')
    const keepAlive = document.getElementById('keep-alive')

    // -----------------
    // Constants
    // -----------------

    const STATUS_ALIVE = 'alive'
    const STATUS_DEAD = 'dead'

    const COMMAND_START = 'start'
    const COMMAND_STOP = 'stop'
    const COMMAND_KEEP_ALIVE = 'keep-alive'

    const EVENT_SERVER_STATUS = 'server-status'
    const EVENT_KEEP_ALIVE_CONFIG = 'keep-alive'

    // -----------------
    // Observers
    // -----------------

    startButton.addEventListener('click', () => {
        disableButtons()
        setInfoLoading()
        if (ws.readyState === WebSocket.OPEN) ws.send(JSON.stringify({event: COMMAND_START}))
    });

    stopButton.addEventListener('click', () => {
        disableButtons()
        setInfoLoading()
        if (ws.readyState === WebSocket.OPEN) ws.send(JSON.stringify({event: COMMAND_STOP}))
    });

    keepAlive.addEventListener('change', setKeepAlive)

    // -----------------
    // Helpers
    // -----------------

    // buttons

    function disableButtons() {
        startButton.disabled = true
        stopButton.disabled = true
    }

    function enableButtons() {
        disableButtons()
        if (!httpServerStatus) startButton.disabled = false
        else stopButton.disabled = false
    }

    // info box

    function setInfoActive() {
        infoBox.classList.remove('inactive', 'loading')
        infoBox.classList.add('active')
        infoBox.innerHTML = 'Active'
    }

    function setInfoInactive() {
        infoBox.classList.remove('active', 'loading')
        infoBox.classList.add('inactive')
        infoBox.innerHTML = 'Inactive'
    }

    function setInfoLoading() {
        infoBox.classList.remove('active', 'inactive')
        infoBox.classList.add('loading')
        infoBox.innerHTML = 'Processing...'
    }

    function setKeepAlive() {
        const message = JSON.stringify({
            event: COMMAND_KEEP_ALIVE,
            data: {
                keepAlive: keepAlive.checked
            },
        })
        if (ws.readyState === WebSocket.OPEN) ws.send(message)
    }

    // website link

    function showWebsiteLink() {
        websiteLink.style.display = 'flex'
    }

    function hideWebsiteLink() {
        websiteLink.style.display = 'none'
    }

    // -----------------
    // WebSocket
    // -----------------

    const ws = new WebSocket('ws://127.0.0.1:8080/')
    ws.onopen =  () => ws.send('hello')
    ws.onmessage = (resp) => {
        const parsedData = JSON.parse(resp.data)
        idBox.innerHTML = parsedData['id']
        const data = parsedData['data']

        switch (data['event']) {
            case EVENT_SERVER_STATUS:
                if (data['data'] === STATUS_ALIVE) {
                    console.log('test')
                    httpServerStatus = true
                    showWebsiteLink()
                    setInfoActive()
                } else if (data['data'] === STATUS_DEAD) {
                    httpServerStatus = false
                    hideWebsiteLink()
                    setInfoInactive()
                }
                break;
            case EVENT_KEEP_ALIVE_CONFIG:
                keepAlive.checked = data['data'] === 'true'
                break;
        }

        enableButtons()
    }
    ws.onclose = hideWebsiteLink
</script>

</body>
</html>
