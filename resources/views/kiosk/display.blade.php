<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entry Logs - IETI</title>

    <style>
        /* ---------- Base ---------- */
        * { box-sizing: border-box; }
        html, body { height: 100%; margin: 0; }

        body {
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
            color: #fff;
            overflow: hidden;
            transition: background 0.25s ease;
        }

        /* Screen colors per state. The body class is switched by JavaScript. */
        body.state-waiting { background: linear-gradient(135deg, #064e29 0%, #0a6b38 100%); }
        body.state-granted { background: #15803d; }
        body.state-denied  { background: #b91c1c; }

        main {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            text-align: center;
        }

        section[hidden] { display: none; }

        /* ---------- Waiting screen ---------- */
        .school   { font-size: clamp(1.2rem, 3vw, 2rem); letter-spacing: 0.15em; opacity: 0.85; }
        .tap-text { font-size: clamp(2.2rem, 7vw, 5rem); font-weight: 800; margin: 16px 0; }
        .clock    { font-size: clamp(1.4rem, 4vw, 2.5rem); font-variant-numeric: tabular-nums; }
        .date     { font-size: clamp(1rem, 2.4vw, 1.5rem); opacity: 0.8; margin-top: 4px; }
        .pulse    { animation: pulse 2s infinite; }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50%      { opacity: 0.5; }
        }

        /* ---------- Result screen ---------- */
        #result { animation: pop 0.35s ease-out; }

        @keyframes pop {
            from { transform: scale(0.94); opacity: 0; }
            to   { transform: scale(1);    opacity: 1; }
        }

        .verdict {
            font-size: clamp(2.4rem, 8vw, 6rem);
            font-weight: 900;
            letter-spacing: 0.04em;
            margin-bottom: 20px;
        }

        .photo-wrap {
            width: clamp(160px, 28vmin, 280px);
            height: clamp(160px, 28vmin, 280px);
            margin: 0 auto 20px;
            border-radius: 24px;
            border: 6px solid #fff;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .photo-wrap img { width: 100%; height: 100%; object-fit: cover; }
        .photo-wrap .initial { font-size: clamp(3rem, 10vmin, 6rem); font-weight: 800; }

        .student-name { font-size: clamp(1.8rem, 5.5vw, 4rem); font-weight: 800; margin: 0; }
        .student-id   { font-size: clamp(1.1rem, 3vw, 2rem); opacity: 0.9; margin: 6px 0 20px; }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: 16px;
            background: rgba(255, 255, 255, 0.22);
            border-radius: 999px;
            padding: 12px 32px;
            font-size: clamp(1.3rem, 3.6vw, 2.4rem);
            font-weight: 700;
        }
        .pill .time { font-variant-numeric: tabular-nums; font-weight: 600; }

        .message { font-size: clamp(1rem, 2.6vw, 1.6rem); margin-top: 20px; opacity: 0.95; }

        /* ---------- Small controls ---------- */
        .fs-btn {
            position: fixed; top: 12px; right: 12px;
            background: rgba(255, 255, 255, 0.18);
            color: #fff; border: 0; border-radius: 8px;
            padding: 8px 14px; font-size: 0.9rem; cursor: pointer;
        }
        .fs-btn:hover { background: rgba(255, 255, 255, 0.3); }

        .conn {
            position: fixed; bottom: 12px; left: 12px;
            background: rgba(0, 0, 0, 0.45);
            border-radius: 8px; padding: 6px 12px; font-size: 0.85rem;
        }
        .conn[hidden] { display: none; }

        @media (prefers-reduced-motion: reduce) {
            .pulse, #result { animation: none; }
            body { transition: none; }
        }
    </style>
</head>
<body class="state-waiting" id="screen">

    <button type="button" class="fs-btn" id="fsBtn">Fullscreen</button>
    <div class="conn" id="conn" hidden>Reconnecting...</div>

    <main>
        <!-- Shown while waiting for a tap -->
        <section id="waiting">
            <div class="school">IETI COLLEGE MARIKINA</div>
            <div class="tap-text pulse">Tap your ID</div>
            <div class="clock" id="clock">--:--:--</div>
            <div class="date" id="date"></div>
        </section>

        <!-- Shown for 5 seconds after a tap -->
        <section id="result" hidden aria-live="assertive">
            <div class="verdict" id="verdict">ACCESS GRANTED</div>

            <div class="photo-wrap">
                <img id="photo" alt="Student photo" hidden>
                <div class="initial" id="initial"></div>
            </div>

            <h1 class="student-name" id="name"></h1>
            <p class="student-id" id="sid"></p>

            <div class="pill">
                <span id="action"></span>
                <span class="time" id="time"></span>
            </div>

            <p class="message" id="message"></p>
        </section>
    </main>

    <script>
        const LATEST_URL = "{{ route('guard.kiosk.latest', [], false) }}";
        const RESULT_SECONDS = 5; 
        const POLL_MS = 1000;     

        const $ = (id) => document.getElementById(id);

        let lastKey = null;        
        let firstLoad = true;      
        let resetTimer = null;

        function updateClock() {
            const now = new Date();
            $('clock').textContent = now.toLocaleTimeString('en-US');
            $('date').textContent = now.toLocaleDateString('en-US', {
                weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
            });
        }
        updateClock();
        setInterval(updateClock, 1000);

        function showScan(scan) {
            const granted = scan.result === 'granted';

            document.body.className = granted ? 'state-granted' : 'state-denied';
            $('verdict').textContent = granted ? 'ACCESS GRANTED' : 'ACCESS DENIED';
            
            // Handle unknown cards vs recognized students
            if (scan.is_unknown) {
                $('name').textContent = 'Unregistered Tag';
                $('sid').textContent = 'UID: ' + (scan.card_uid || 'Unknown');
                $('initial').textContent = '⚠️';
            } else {
                $('name').textContent = scan.name;
                $('sid').textContent = scan.student_number;
                $('initial').textContent = scan.initial;
            }

            $('time').textContent = scan.time;

            if (granted) {
                $('action').textContent = scan.action === 'IN' ? 'TIME IN' : 'TIME OUT';
                $('message').textContent = scan.action === 'IN' ? 'Welcome to IETI!' : 'Have a safe trip home!';
            } else {
                $('action').textContent = scan.is_unknown ? 'UNKNOWN CARD' : 'NOT ALLOWED';
                $('message').textContent = scan.is_unknown 
                    ? 'This RFID tag is not registered in the system.' 
                    : 'Please proceed to the registrar\'s office.';
            }

            // Photo / Avatar handling
            const photo = $('photo');
            if (!scan.is_unknown && scan.photo) {
                photo.onerror = () => { photo.hidden = true; $('initial').hidden = false; };
                photo.src = scan.photo;
                photo.hidden = false;
                $('initial').hidden = true;
            } else {
                photo.hidden = true;
                $('initial').hidden = false;
            }

            $('waiting').hidden = true;

            const result = $('result');
            result.hidden = true;
            void result.offsetWidth;
            result.hidden = false;

            clearTimeout(resetTimer);
            resetTimer = setTimeout(showWaiting, RESULT_SECONDS * 1000);
        }

        function showWaiting() {
            document.body.className = 'state-waiting';
            $('result').hidden = true;
            $('waiting').hidden = false;
        }

        async function poll() {
            try {
                const res = await fetch(LATEST_URL, {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin',
                    cache: 'no-store',
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);

                const data = await res.json();
                $('conn').hidden = true;

                const scan = data.scan;

                if (firstLoad) {
                    lastKey = scan ? scan.key : null;
                    firstLoad = false;
                } else if (scan && scan.key !== lastKey) {
                    lastKey = scan.key;
                    showScan(scan);
                }
            } catch (e) {
                $('conn').hidden = false;
            } finally {
                setTimeout(poll, POLL_MS);
            }
        }
        poll();

        $('fsBtn').addEventListener('click', () => {
            if (document.documentElement.requestFullscreen) {
                document.documentElement.requestFullscreen();
            }
        });
        document.addEventListener('fullscreenchange', () => {
            $('fsBtn').hidden = !!document.fullscreenElement;
        });
    </script>

</body>
</html>