<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entry Verification - IETI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .bg-ieti-green { background-color: #064e29; }
        body { margin: 0; padding: 0; overflow: hidden; }
        .kiosk-container {
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #064e29 0%, #0a6b38 100%);
        }
        .welcome-screen {
            text-align: center;
            color: white;
        }
        .welcome-text {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        .instruction-text {
            font-size: 1.5rem;
            opacity: 0.9;
            margin-top: 30px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 0.9; }
            50% { opacity: 0.5; }
        }
        .student-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            max-width: 600px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            animation: slideIn 0.5s ease-out;
        }
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .student-photo {
            width: 200px;
            height: 200px;
            background: linear-gradient(135deg, #064e29 0%, #0a6b38 100%);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            font-size: 4rem;
            font-weight: bold;
            color: white;
        }
        .student-name {
            font-size: 2.5rem;
            font-weight: bold;
            color: #064e29;
            margin: 20px 0;
        }
        .student-info {
            font-size: 1.2rem;
            color: #666;
            margin: 10px 0;
        }
        .time-display {
            font-size: 2rem;
            font-weight: bold;
            color: #064e29;
            margin: 30px 0 20px;
            font-family: 'Courier New', monospace;
        }
        .status-badge {
            display: inline-block;
            background: #10b981;
            color: white;
            padding: 15px 40px;
            border-radius: 50px;
            font-size: 1.3rem;
            font-weight: bold;
            margin: 20px 0;
        }
        .enrollment-badge {
            display: inline-block;
            background: #3b82f6;
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 0.9rem;
            margin: 15px 0;
        }
        .greeting {
            font-size: 2rem;
            color: #064e29;
            margin-top: 30px;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <div class="kiosk-container">
        
        <!-- Welcome Screen (Default) -->
        <div id="welcomeScreen" class="welcome-screen">
            <div class="welcome-text">Welcome to IETI</div>
            <svg class="w-32 h-32 mx-auto text-white opacity-80" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"/>
            </svg>
            <div class="instruction-text">📱 Tap Your RFID Card</div>
        </div>

        <!-- Student Entry Screen (Hidden by default) -->
        <div id="studentScreen" class="hidden">
            <div class="student-card">
                <div class="student-photo" id="studentInitial">J</div>
                
                <div class="student-name" id="studentName">John Doe</div>
                <div class="student-info" id="studentId">Student ID: MAR87654320</div>
                <div class="student-info" id="studentDetails">Year 2 - Section S4B1</div>
                
                <div class="enrollment-badge" id="enrollmentStatus">✅ Enrolled</div>
                
                <div class="time-display" id="timeDisplay">08:30:00</div>
                
                <div class="status-badge" id="statusBadge">✅ Entry Recorded</div>
                
                <div class="greeting">Have a Great Day! 👋</div>
            </div>
        </div>

    </div>

    <!-- Kiosk Logic -->
    <script>
        // Simulate entry log update (in real implementation, this will receive WebSocket/AJAX updates)
        function displayStudentEntry(data) {
            // Hide welcome, show student
            document.getElementById('welcomeScreen').classList.add('hidden');
            document.getElementById('studentScreen').classList.remove('hidden');

            // Update student info
            document.getElementById('studentInitial').textContent = data.first_name.charAt(0).toUpperCase();
            document.getElementById('studentName').textContent = data.first_name + ' ' + data.last_name;
            document.getElementById('studentId').textContent = 'Student ID: ' + data.student_number;
            document.getElementById('studentDetails').textContent = 'Year ' + data.year_level + ' - Section ' + data.section;
            
            // Update enrollment status
            const enrollmentBadge = document.getElementById('enrollmentStatus');
            enrollmentBadge.textContent = (data.status === 'Enrolled' ? '✅ Enrolled' : '❌ Unenrolled');
            enrollmentBadge.className = data.status === 'Enrolled' ? 'enrollment-badge' : 'enrollment-badge bg-red-500';

            // Update time
            document.getElementById('timeDisplay').textContent = data.time_in || new Date().toLocaleTimeString('en-US', {hour12: false});

            // Reset after 5 seconds
            setTimeout(() => {
                document.getElementById('welcomeScreen').classList.remove('hidden');
                document.getElementById('studentScreen').classList.add('hidden');
            }, 5000);
        }

        // Example usage (will be called from real entry log event):
        // displayStudentEntry({
        //     first_name: 'John',
        //     last_name: 'Doe',
        //     student_number: 'MAR87654320',
        //     year_level: 2,
        //     section: 'S4B1',
        //     status: 'Enrolled',
        //     time_in: '08:30'
        // });

        // Clock animation
        setInterval(() => {
            const now = new Date();
            if (!document.getElementById('studentScreen').classList.contains('hidden')) {
                document.getElementById('timeDisplay').textContent = now.toLocaleTimeString('en-US', {hour12: false});
            }
        }, 1000);
    </script>

</body>
</html>