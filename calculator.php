<?php
// --- Add this at the top of the file ---
session_start();
$servername = "localhost";
$dbusername = "root";
$dbpassword = "";
$dbname = "ecohabitsdb";

// Connect to DB
$pdo = new PDO("mysql:host=$servername;dbname=$dbname", $dbusername, $dbpassword);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$user_id = $_SESSION['user_id'] ?? null;

// Handle AJAX save request
if (isset($_POST['save_calculation']) && $user_id) {
    $footprint = floatval($_POST['footprint']);
    $details = json_encode($_POST['details']);
    $date = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("INSERT INTO carbon_history (user_id, footprint, details, created_at) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $footprint, $details, $date]);
    $newId = $pdo->lastInsertId();
    echo json_encode([
        'success' => true,
        'id' => $newId,
        'created_at' => $date,
        'footprint' => $footprint,
        'details' => $_POST['details']
    ]);
    exit();
}
// Handle AJAX delete request
if (isset($_POST['delete_history']) && $user_id && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $stmt = $pdo->prepare("DELETE FROM carbon_history WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    echo json_encode(['success' => true]);
    exit();
}
// Handle AJAX update request
if (isset($_POST['update_history']) && $user_id && isset($_POST['id']) && isset($_POST['footprint']) && isset($_POST['details'])) {
    $id = intval($_POST['id']);
    $footprint = floatval($_POST['footprint']);
    $details = json_encode(json_decode($_POST['details'], true));
    $stmt = $pdo->prepare("UPDATE carbon_history SET footprint = ?, details = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$footprint, $details, $id, $user_id]);
    echo json_encode(['success' => true]);
    exit();
}

// Fetch user history if logged in
$user_history = [];
if ($user_id) {
    $stmt = $pdo->prepare("SELECT * FROM carbon_history WHERE user_id = ? ORDER BY created_at DESC LIMIT 12");
    $stmt->execute([$user_id]);
    $user_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<?php include __DIR__ . '/includes/cookie_consent.php'; ?>
<?php include __DIR__ . '/includes/topbar.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calculator | EcoHabits</title>
    <link rel="icon" type="image/png" href="assets/images/EcoHabits_logo.png">
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <style>
        /* Base Styles */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    line-height: 1.8;
    color: #333;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
    background-color: #f8faf8;
    background-image: 
        radial-gradient(#1F8D49 0.5px, transparent 0.5px),
        radial-gradient(#1F8D49 0.5px, #f8faf8 0.5px);
    background-size: 20px 20px;
    background-position: 0 0, 10px 10px;
    background-attachment: fixed;
        }

        .calculator {
            background-color: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 40px;
            position: relative;
        }

        .calculator::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #1F8D49, #34A853);
        }

        .calculator h2 {
            color: #1F8D49;
            margin-top: 0;
            margin-bottom: 30px;
            font-size: 26px;
        }

        .calculator h3 {
            color: #2c6e36;
            margin-top: 35px;
            margin-bottom: 20px;
            font-size: 19px;
            display: flex;
            align-items: center;
            border-bottom: 2px solid #f0f8f0;
            padding-bottom: 10px;
        }

        .calculator h3::before {
            content: '';
            display: inline-block;
            width: 8px;
            height: 8px;
            background-color: #1F8D49;
            border-radius: 50%;
            margin-right: 10px;
        }

        /* Organized form layout */
        .form-section {
            margin-bottom: 30px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        label {
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
            display: flex;
            align-items: center;
        }

        .info-icon {
            display: inline-block;
            width: 18px;
            height: 18px;
            background-color: #1F8D49;
            color: white;
            border-radius: 50%;
            text-align: center;
            line-height: 18px;
            font-size: 12px;
            margin-left: 8px;
            cursor: help;
            position: relative;
            vertical-align: middle;
        }

        .info-tooltip {
            position: absolute;
            bottom: calc(100% + 8px);
            left: 50%;
            transform: translateX(-50%);
            background-color: #333;
            color: white;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 14px;
            width: 220px;
            z-index: 10;
            display: none;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .info-icon:hover .info-tooltip {
            display: block;
        }

        input[type="number"],
        select {
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            width: 100%;
            box-sizing: border-box;
        }

        input[type="number"]:focus,
        select:focus {
            outline: none;
            border-color: #1F8D49;
            box-shadow: 0 0 0 3px rgba(31, 141, 73, 0.1);
        }

        .submit-button {
            background-color: #1F8D49;
            color: white;
            border: none;
            padding: 14px 20px;
            font-size: 18px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
            width: 100%;
            max-width: 300px;
        }

        .submit-button:hover {
            background-color: #177a3b;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(23, 122, 59, 0.2);
        }

        /* Results Section */
        #result {
            margin-top: 30px;
            padding: 25px;
            background-color: #f9fcf9;
            border-radius: 8px;
            border-left: 4px solid #1F8D49;
            display: none;
        }

        #result h3 {
            color: #1F8D49;
            margin-top: 0;
            font-size: 22px;
        }

        .footprint-display {
            display: flex;
            align-items: center;
            margin: 20px 0;
        }

        .footprint-value {
            font-size: 32px;
            font-weight: 700;
            margin-right: 15px;
            color: #1F8D49;
        }

        .footprint-label {
            font-size: 18px;
        }

        .comparison-chart {
            width: 100%;
            height: 30px;
            background-color: #f0f0f0;
            border-radius: 15px;
            margin: 20px 0;
            overflow: hidden;
            position: relative;
        }

        .chart-bar {
            height: 100%;
            background: linear-gradient(90deg, #4CAF50, #1F8D49);
            position: relative;
            transition: width 1s ease;
        }

        .chart-markers {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: space-between;
        }

        .chart-marker {
            position: relative;
            width: 1px;
            height: 100%;
            background-color: rgba(0,0,0,0.2);
        }

        .chart-marker::after {
            content: attr(data-value);
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            font-size: 12px;
            white-space: nowrap;
        }

        .benchmarks {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }

        .benchmark {
            text-align: center;
            padding: 15px;
            border-radius: 8px;
            width: 30%;
            background-color: #f5f5f5;
        }

        .benchmark h4 {
            margin-top: 0;
            color: #2c6e36;
        }

        .benchmark-value {
            font-weight: bold;
            font-size: 18px;
            margin: 10px 0;
        }

        #tips {
            margin-top: 30px;
        }

        #tips h4 {
            margin-bottom: 10px;
            color: #2c6e36;
        }

        #tipsList {
            padding-left: 20px;
        }

        #tipsList li {
            margin-bottom: 8px;
            position: relative;
            padding-left: 5px;
        }

        #tipsList li::before {
            content: '•';
            color: #1F8D49;
            font-weight: bold;
            position: absolute;
            left: -15px;
        }

        .footprint-scale {
            margin: 30px 0;
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
        }

        .scale-title {
            text-align: center;
            margin-bottom: 15px;
            color: #2c6e36;
            font-weight: 600;
        }

        .scale-levels {
            display: flex;
            height: 40px;
            border-radius: 20px;
            overflow: hidden;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .scale-level {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 12px;
            text-shadow: 0 1px 1px rgba(0,0,0,0.3);
        }

        .level-1 { background-color: #4CAF50; }
        .level-2 { background-color: #8BC34A; }
        .level-3 { background-color: #FFC107; }
        .level-4 { background-color: #FF9800; }
        .level-5 { background-color: #F44336; }

        .scale-labels {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
        }

        .scale-label {
            text-align: center;
            width: 20%;
            padding: 0 5px;
        }

        .scale-label-title {
            font-weight: 600;
            color: #2c6e36;
            margin-bottom: 5px;
        }

        .scale-label-range {
            font-size: 13px;
            color: #666;
        }

        .user-marker {
            position: relative;
            height: 60px;
            margin-top: -10px;
            /* Removed display: flex; and justify-content: center; */
        }
        .marker-line {
            width: 2px;
            height: 40px;
            background-color: #333;
            position: absolute;
            left: 50%; /* Will be set dynamically */
            top: 0;
        }
        .marker-label {
            position: absolute;
            top: 40px;
            left: 50%; /* Will be set dynamically */
            background-color: #333;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            transform: translateX(-50%);
            white-space: nowrap;
        }
        .marker-label::after {
            content: '';
            position: absolute;
            top: -5px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 5px solid transparent;
            border-right: 5px solid transparent;
            border-bottom: 5px solid #333;
        }

        /* Instruction Section */
        .instruction {
            background-color: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            margin-bottom: 40px;
        }

        .instruction h2 {
            color: #1F8D49;
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 24px;
        }

        .instruction-steps {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 30px;
        }

        .step {
            flex: 1;
            min-width: 250px;
            padding: 20px;
            background-color: #f9fcf9;
            border-radius: 8px;
            border-left: 3px solid #1F8D49;
        }

        .step-number {
            display: inline-block;
            width: 30px;
            height: 30px;
            background-color: #1F8D49;
            color: white;
            border-radius: 50%;
            text-align: center;
            line-height: 30px;
            font-weight: bold;
            margin-right: 10px;
        }

        .step h3 {
            display: inline;
            margin: 0;
            color: #2c6e36;
        }

        .step p {
            margin-top: 10px;
            margin-bottom: 0;
        }

        .faq {
            margin-top: 30px;
        }

        .faq-item {
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }

        .faq-question {
            font-weight: 600;
            color: #2c6e36;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .faq-answer {
            margin-top: 10px;
            display: none;
        }

        .faq-answer.show {
            display: block;
        }

        /* History Section */
        .history {
            background-color: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            margin-bottom: 60px;
        }

        .history h2 {
            color: #1F8D49;
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 24px;
        }

        .history table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .history th, .history td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }

        .history th {
            background-color: #f9fcf9;
            color: #2c6e36;
            font-weight: 600;
        }

        .history tr:hover {
            background-color: #f9fcf9;
        }

        .history a {
            color: #1F8D49;
            text-decoration: none;
            font-weight: 500;
        }

        .history a:hover {
            text-decoration: underline;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            
            .form-row {
                grid-template-columns: 1fr;
            }

            .calculator, .instruction, .history {
                padding: 20px;
            }

            .calculator h2, .instruction h2, .history h2 {
                font-size: 22px;
            }

            .benchmarks {
                flex-direction: column;
                gap: 15px;
            }

            .benchmark {
                width: 100%;
            }

            .instruction-steps {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            .submit-button {
                font-size: 16px;
                padding: 12px;
            }

            .footprint-display {
                flex-direction: column;
                align-items: flex-start;
            }

            .footprint-value {
                margin-bottom: 5px;
            }
        }
        /* Modal overlay */
        #historyModal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0; top: 0;
            width: 100vw; height: 100vh;
            background: rgba(0,0,0,0.4);
            align-items: center;
            justify-content: center;
        }
        /* Modal content */
        #modalContent {
            background: #fff;
            border-radius: 16px;
            max-width: 420px;
            margin: auto;
            padding: 32px 28px 24px 28px;
            position: relative;
            box-shadow: 0 8px 32px rgba(0,0,0,0.18);
            animation: fadeIn 0.2s;
            max-height: 90vh;
            overflow-y: auto;
        }
        @keyframes fadeIn {
            from { transform: translateY(40px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        /* Modal title */
        #modalView h3, #modalContent h3 {
            margin-top: 0;
            margin-bottom: 18px;
            font-size: 1.3em;
            color: #1F8D49;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        /* Close button */
        #closeModal {
            position: absolute;
            top: 12px; right: 16px;
            background: none;
            border: none;
            font-size: 26px;
            color: #333;
            cursor: pointer;
            transition: color 0.2s;
        }
        #closeModal:hover { color: #1F8D49; }
        /* Modal list */
        #modalView ul {
            padding-left: 18px;
            margin-bottom: 0;
        }
        #modalView li {
            margin-bottom: 8px;
            font-size: 1.05em;
        }
        #modalView li b {
            color: #1F8D49;
        }
        /* Modal form fields */
        #editHistoryForm label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #2c6e36;
        }
        #editHistoryForm input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 7px;
            font-size: 1em;
            margin-bottom: 16px;
            box-sizing: border-box;
            transition: border 0.2s;
        }
        #editHistoryForm input:focus {
            border-color: #1F8D49;
            outline: none;
        }
        /* Save button */
        #editHistoryForm button[type='submit'] {
            background: #1F8D49;
            color: #fff;
            border: none;
            padding: 12px 28px;
            border-radius: 7px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, box-shadow 0.2s;
            margin-top: 8px;
        }
        #editHistoryForm button[type='submit']:hover {
            background: #177a3b;
            box-shadow: 0 2px 8px rgba(31,141,73,0.12);
        }
        /* Table action buttons */
        .view-btn, .edit-btn, .delete-btn {
            background: #f5f5f5;
            color: #1F8D49;
            border: 1px solid #1F8D49;
            border-radius: 6px;
            padding: 6px 14px;
            font-size: 0.98em;
            margin-right: 6px;
            margin-bottom: 2px;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
        }
        .edit-btn { color: #FFA000; border-color: #FFA000; }
        .delete-btn { color: #D32F2F; border-color: #D32F2F; }
        .view-btn:hover { background: #e8f7ee; }
        .edit-btn:hover { background: #fff7e0; }
        .delete-btn:hover { background: #ffeaea; }
    </style>
</head>
<body>

    <!-- Calculator -->
    <div class="calculator">
        <h2>Calculate Your Carbon Footprint</h2>
        <p>Complete the form below to estimate your annual carbon emissions. We'll provide personalized recommendations to help you reduce your impact.</p>
        
        <form id="carbonForm">
            <div class="form-section">
                <h3>Home Energy</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="electricity">
                            Monthly Electricity (kWh):
                            <span class="info-icon">i<span class="info-tooltip">Find this on your TNB bill. Average Malaysian household uses about 350-400 kWh/month.</span></span>
                        </label>
                        <input type="number" id="electricity" min="0" placeholder="e.g. 380" value="380">
                    </div>
                    
                    <div class="form-group">
                        <label for="lpg">
                            Monthly LPG (kg):
                            <span class="info-icon">i<span class="info-tooltip">Estimate your cooking gas usage. A typical 14kg LPG tank lasts 1-2 months for a family.</span></span>
                        </label>
                        <input type="number" id="lpg" min="0" placeholder="e.g. 7">
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h3>Transportation</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="carMileage">
                            Annual Car Mileage (km):
                            <span class="info-icon">i<span class="info-tooltip">Check your odometer or estimate. Average Malaysian drives about 18,000 km/year.</span></span>
                        </label>
                        <input type="number" id="carMileage" min="0" placeholder="e.g. 18000" value="18000">
                    </div>
                    
                    <div class="form-group">
                        <label for="carEfficiency">
                            Your Car's Fuel Efficiency (km/litre):
                            <span class="info-icon">i<span class="info-tooltip">Find this in your owner's manual. Average is 14 km/litre for Malaysian cars.</span></span>
                        </label>
                        <input type="number" id="carEfficiency" min="0" placeholder="e.g. 14" value="14">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="flights">
                            Annual Flight Hours:
                            <span class="info-icon">i<span class="info-tooltip">Estimate total hours in the air. A roundtrip KL-London is about 26 hours.</span></span>
                        </label>
                        <input type="number" id="flights" min="0" placeholder="e.g. 5">
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h3>Lifestyle</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="diet">
                            Primary Diet:
                            <span class="info-icon">i<span class="info-tooltip">Your diet significantly impacts your carbon footprint.</span></span>
                        </label>
                        <select id="diet">
                            <option value="meatDaily">Meat Daily</option>
                            <option value="meatWeekly">Meat Weekly</option>
                            <option value="vegetarian">Vegetarian</option>
                            <option value="vegan">Vegan</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="household">
                            Household Size:
                            <span class="info-icon">i<span class="info-tooltip">We'll divide the total by number of people for per-person footprint.</span></span>
                        </label>
                        <input type="number" id="household" min="1" value="4">
                    </div>
                </div>
            </div>
            
            <button type="submit" class="submit-button">Calculate My Footprint</button>
        </form>
        
        <div id="result">
            <h3>Your Carbon Footprint Results</h3>
            
            <div class="footprint-display">
                <div class="footprint-value" id="footprintResult">0</div>
                <div class="footprint-label">tons of CO₂ per year</div>
            </div>
            
            <div class="comparison-chart">
                <div class="chart-bar" id="chartBar" style="width: 0%"></div>
                <div class="chart-markers">
                    <div class="chart-marker" style="left: 0%" data-value="0"></div>
                    <div class="chart-marker" style="left: 25%" data-value="5"></div>
                    <div class="chart-marker" style="left: 50%" data-value="10"></div>
                    <div class="chart-marker" style="left: 75%" data-value="15"></div>
                    <div class="chart-marker" style="left: 100%" data-value="20+"></div>
                </div>
            </div>
            
            <div id="footprintAssessment">
                <!-- Filled by JavaScript -->
            </div>
            
            <div class="benchmarks">
                <div class="benchmark">
                    <h4>Malaysia Average</h4>
                    <div class="benchmark-value">4.2 tons</div>
                    <p>Current per person average</p>
                </div>
                <div class="benchmark">
                    <h4>World Average</h4>
                    <div class="benchmark-value">4.7 tons</div>
                    <p>Current per person average</p>
                </div>
                <div class="benchmark">
                    <h4>Global Target</h4>
                    <div class="benchmark-value">2.0 tons</div>
                    <p>Per person by 2050 to limit warming to 1.5°C</p>
                </div>
            </div>
            
            <div class="footprint-scale">
                <div class="scale-title">How Does Your Footprint Compare?</div>
                
                <div class="scale-levels">
                    <div class="scale-level level-1">Excellent</div>
                    <div class="scale-level level-2">Good</div>
                    <div class="scale-level level-3">Average</div>
                    <div class="scale-level level-4">High</div>
                    <div class="scale-level level-5">Very High</div>
                </div>
                
                <div class="user-marker" id="userMarker">
                    <!-- Filled by JavaScript -->
                </div>
                
                <div class="scale-labels">
                    <div class="scale-label">
                        <div class="scale-label-title">Sustainable</div>
                        <div class="scale-label-range">0-2 tons</div>
                        <div>Meets climate goals</div>
                    </div>
                    <div class="scale-label">
                        <div class="scale-label-title">Good</div>
                        <div class="scale-label-range">2-4 tons</div>
                        <div>Below Malaysia avg</div>
                    </div>
                    <div class="scale-label">
                        <div class="scale-label-title">Average</div>
                        <div class="scale-label-range">4-7 tons</div>
                        <div>Typical Malaysia</div>
                    </div>
                    <div class="scale-label">
                        <div class="scale-label-title">High</div>
                        <div class="scale-label-range">7-10 tons</div>
                        <div>Above average</div>
                    </div>
                    <div class="scale-label">
                        <div class="scale-label-title">Very High</div>
                        <div class="scale-label-range">10+ tons</div>
                        <div>Urgent reduction needed</div>
                    </div>
                </div>
            </div>
            
            <div id="tips">
                <h4>Personalized Reduction Tips</h4>
                <ul id="tipsList"></ul>
            </div>
        </div>
    </div>

    <!-- Instruction Section -->
    <div class="instruction">
        <h2>How To Use This Calculator</h2>
        <p>Follow these steps to get the most accurate estimate of your carbon footprint:</p>
        
        <div class="instruction-steps">
            <div class="step">
                <span class="step-number">1</span>
                <h3>Gather Your Data</h3>
                <p>Collect your utility bills, car maintenance records, and think about your travel and eating habits from the past year.</p>
            </div>
            
            <div class="step">
                <span class="step-number">2</span>
                <h3>Enter Your Information</h3>
                <p>Fill in each field as accurately as possible. Don't worry if you're not exact - estimates are fine!</p>
            </div>
            
            <div class="step">
                <span class="step-number">3</span>
                <h3>Calculate & Learn</h3>
                <p>See your results compared to averages and get personalized tips for reducing your impact.</p>
            </div>
        </div>
        
        <div class="faq">
            <h3>Frequently Asked Questions</h3>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleAnswer(this)">
                    What is the average carbon footprint of a Malaysian?
                    <span>+</span>
                </div>
                <div class="faq-answer">
                    <p>As of recent estimates, the average Malaysian has a carbon footprint of about 7-8 tons of CO₂ per year, which is higher than the global average. This is mainly due to energy use, transportation, and consumption patterns in Malaysia.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleAnswer(this)">
                    How does Malaysia's energy mix affect my carbon footprint?
                    <span>+</span>
                </div>
                <div class="faq-answer">
                    <p>Malaysia relies heavily on fossil fuels, especially natural gas and coal, for electricity generation. This increases the carbon intensity of electricity use. Using less electricity or switching to renewable sources where possible can help reduce your footprint.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleAnswer(this)">
                    What are some Malaysia-specific ways to reduce my carbon footprint?
                    <span>+</span>
                </div>
                <div class="faq-answer">
                    <p>Some effective ways include using public transport (like LRT, MRT, and buses), reducing air conditioning use, supporting local food and products, recycling, and participating in community tree-planting initiatives.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleAnswer(this)">
                    How does public transport in Malaysia impact emissions?
                    <span>+</span>
                </div>
                <div class="faq-answer">
                    <p>Using public transport in Malaysia can significantly reduce per-person emissions compared to driving a private car. The government is expanding rail and bus networks to make this option more accessible and eco-friendly.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleAnswer(this)">
                    Are there government incentives in Malaysia for reducing carbon emissions?
                    <span>+</span>
                </div>
                <div class="faq-answer">
                    <p>Yes, there are incentives such as tax relief for purchasing energy-efficient appliances, rebates for solar panel installations, and grants for green technology projects. Check the latest from the Sustainable Energy Development Authority (SEDA) Malaysia and related agencies.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- History Section -->
    <div class="history">
        <h2>Your Calculation History</h2>
        <?php if ($user_id): ?>
        <table id="historyTable">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Footprint (tons)</th>
                    <th>Details</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($user_history)): ?>
                    <?php foreach ($user_history as $row): ?>
                    <tr data-id="<?= $row['id'] ?>">
                        <td><?= htmlspecialchars(date('M d, Y', strtotime($row['created_at']))) ?></td>
                        <td class="footprint-cell"><?= htmlspecialchars(number_format($row['footprint'], 2)) ?></td>
                        <td>
                            <button class="view-btn" data-details='<?= htmlspecialchars($row['details'], ENT_QUOTES) ?>'>View</button>
                        </td>
                        <td>
                            <button class="edit-btn" data-id="<?= $row['id'] ?>" data-details='<?= htmlspecialchars($row['details'], ENT_QUOTES) ?>' data-footprint="<?= htmlspecialchars(number_format($row['footprint'], 2)) ?>">Edit</button>
                            <button class="delete-btn" data-id="<?= $row['id'] ?>">Delete</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr id="noHistoryRow">
                        <td colspan="4">No history yet. Calculate to save your first result!</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <div id="historyProgress" style="margin-top: 20px; display: none;">
            <h3>Track Your Progress</h3>
            <div id="progressChart">
                <!-- Progress visualization will be added here -->
            </div>
        </div>
        
        <?php else: ?>
        <p><b>Sign in or create an account to save and view your calculation history.</b></p>
        <p>Logged-in users can save their results and track improvements over time. <a href="LoginPage.php" style="color:#1F8D49;">Sign in</a> to start your reduction journey!</p>
        <?php endif; ?>
    </div>

    <div id="historyModal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100vw; height:100vh; background:rgba(0,0,0,0.4); align-items:center; justify-content:center;">
        <div id="modalContent" style="background:#fff; border-radius:10px; max-width:400px; margin:auto; padding:30px; position:relative;">
            <button id="closeModal" style="position:absolute; top:10px; right:10px; background:none; border:none; font-size:20px; cursor:pointer;">&times;</button>
            <div id="modalView"></div>
        </div>
    </div>

    <script>
        // Toggle FAQ answers
        function toggleAnswer(question) {
            const answer = question.nextElementSibling;
            const toggleIcon = question.querySelector('span');
            
            answer.classList.toggle('show');
            toggleIcon.textContent = answer.classList.contains('show') ? '−' : '+';
        }

        // Function to check if user is logged in
        function isLoggedIn() {
            return <?php echo $user_id ? 'true' : 'false'; ?>;
        }

        // Function to update history table immediately
        function updateHistoryTable(newEntry) {
            const historyTable = document.getElementById('historyTable');
            if (!historyTable) return;
            
            // Remove 'No history yet' row if present
            const noHistoryRow = document.getElementById('noHistoryRow');
            if (noHistoryRow) {
                noHistoryRow.remove();
            }
            
            // Create new row
            const newRow = document.createElement('tr');
            newRow.setAttribute('data-id', newEntry.id);
            
            // Format date
            const createdAt = new Date(newEntry.created_at);
            const dateStr = createdAt.toLocaleString('default', { month: 'short' }) + ' ' + 
                           createdAt.getDate().toString().padStart(2, '0') + ', ' + 
                           createdAt.getFullYear();
            
            // Create row HTML
            newRow.innerHTML = `
                <td>${dateStr}</td>
                <td class="footprint-cell">${parseFloat(newEntry.footprint).toFixed(2)}</td>
                <td>
                    <button class="view-btn" data-details='${JSON.stringify(newEntry.details)}'>View</button>
                </td>
                <td>
                    <button class="edit-btn" data-id="${newEntry.id}" data-details='${JSON.stringify(newEntry.details)}' data-footprint="${parseFloat(newEntry.footprint).toFixed(2)}">Edit</button>
                    <button class="delete-btn" data-id="${newEntry.id}">Delete</button>
                </td>
            `;
            
            // Insert as first row in tbody (after header)
            const tbody = historyTable.querySelector('tbody');
            if (tbody.firstElementChild) {
                tbody.insertBefore(newRow, tbody.firstElementChild);
            } else {
                tbody.appendChild(newRow);
            }
            
            // Attach event handlers to new buttons
            const viewBtn = newRow.querySelector('.view-btn');
            const editBtn = newRow.querySelector('.edit-btn');
            const deleteBtn = newRow.querySelector('.delete-btn');
            
            viewBtn.onclick = viewBtnHandler;
            editBtn.onclick = editBtnHandler;
            deleteBtn.onclick = deleteBtnHandler;
            
            // Add visual feedback for new entry
            newRow.style.backgroundColor = '#f0f8f0';
            newRow.style.transition = 'background-color 0.3s ease';
            setTimeout(() => {
                newRow.style.backgroundColor = '';
            }, 3000);
            
            // Keep only the latest 12 entries (matching your PHP LIMIT)
            const allRows = tbody.querySelectorAll('tr[data-id]');
            if (allRows.length > 12) {
                allRows[allRows.length - 1].remove();
            }
            
            // Show a success message
            showSuccessMessage('Calculation saved successfully!');
        }

        // Function to show success message
        function showSuccessMessage(message) {
            const successDiv = document.createElement('div');
            successDiv.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #1F8D49;
                color: white;
                padding: 12px 20px;
                border-radius: 8px;
                font-weight: 500;
                z-index: 1000;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                animation: slideIn 0.3s ease;
            `;
            successDiv.textContent = message;
            
            // Add CSS animation
            if (!document.getElementById('successAnimation')) {
                const style = document.createElement('style');
                style.id = 'successAnimation';
                style.textContent = `
                    @keyframes slideIn {
                        from { transform: translateX(100%); opacity: 0; }
                        to { transform: translateX(0); opacity: 1; }
                    }
                `;
                document.head.appendChild(style);
            }
            
            document.body.appendChild(successDiv);
            
            setTimeout(() => {
                successDiv.remove();
            }, 3000);
        }

        // Calculate carbon footprint
        document.getElementById('carbonForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get values
            const electricity = parseFloat(document.getElementById('electricity').value) || 0;
            const lpg = parseFloat(document.getElementById('lpg').value) || 0;
            const carMileage = parseFloat(document.getElementById('carMileage').value) || 0;
            const carEfficiency = parseFloat(document.getElementById('carEfficiency').value) || 1;
            const flights = parseFloat(document.getElementById('flights').value) || 0;
            const diet = document.getElementById('diet').value;
            const household = parseFloat(document.getElementById('household').value) || 1;
            
            // Malaysian emission factors
            // Electricity: 0.63 kg CO2/kWh (Malaysia grid)
            // LPG: 1.51 kg CO2/kg
            // Car: 2.31 kg CO2/litre petrol
            // Flights: 0.55 tons/hour (global average)
            // Diet: same as before
            
            const electricityCO2 = electricity * 0.63 * 12 / 1000; // tons/year
            const lpgCO2 = lpg * 1.51 * 12 / 1000; // tons/year
            const carCO2 = (carMileage / carEfficiency) * 2.31 / 1000; // tons/year
            const flightsCO2 = flights * 0.55; // tons/year
            
            let dietCO2 = 0;
            switch(diet) {
                case 'meatDaily': dietCO2 = 2.8; break; // Malaysia: slightly lower than US
                case 'meatWeekly': dietCO2 = 2.0; break;
                case 'vegetarian': dietCO2 = 1.3; break;
                case 'vegan': dietCO2 = 1.1; break;
            }
            
            // Calculate total per person
            const totalCO2 = (electricityCO2 + lpgCO2 + carCO2 + flightsCO2 + dietCO2) / household;
            
            // Display results
            document.getElementById('footprintResult').textContent = totalCO2.toFixed(1);
            
            // Update chart
            const chartPercentage = Math.min(totalCO2 / 15 * 100, 100);
            document.getElementById('chartBar').style.width = chartPercentage + '%';
            
            // Update scale marker
            const userMarker = document.getElementById('userMarker');
            userMarker.innerHTML = '';
            
            const markerPosition = Math.min(totalCO2 / 15 * 100, 100);
            
            const markerLine = document.createElement('div');
            markerLine.className = 'marker-line';
            markerLine.style.left = markerPosition + '%';
            userMarker.appendChild(markerLine);
            
            const markerLabel = document.createElement('div');
            markerLabel.className = 'marker-label';
            markerLabel.textContent = 'You: ' + totalCO2.toFixed(1) + ' tons';
            markerLabel.style.left = markerPosition + '%';
            userMarker.appendChild(markerLabel);
            
            // Position the marker
            // const markerPosition = Math.min(totalCO2 / 20 * 100, 100);
            // userMarker.style.left = markerPosition + '%';

            const assessment = document.getElementById('footprintAssessment');
            // Provide assessment
            if (totalCO2 < 2) {
                assessment.innerHTML = `<p style="color:#1F8D49; font-weight:bold;">Excellent! Your footprint is below Malaysia's and global averages. Keep it up!</p>`;
            } else if (totalCO2 < 4.2) {
                assessment.innerHTML = `<p style=\"color:#FFA000;\">You're below the Malaysian average (4.2 tons). Aim for the 2-ton global target!</p>`;
            } else {
                assessment.innerHTML = `<p style=\"color:#D32F2F;\">Your footprint is above Malaysia's average. See our tips below to reduce your impact.</p>`;
            }
            
            // Generate tips
            const tipsList = document.getElementById('tipsList');
            tipsList.innerHTML = '';
            
            const tips = [];
            
            if (electricityCO2 > 1) {
                tips.push({
                    priority: 1,
                    text: "Switch to energy-efficient appliances and use fans instead of air conditioning when possible."
                });
            }
            
            if (lpgCO2 > 0.5) {
                tips.push({
                    priority: 1,
                    text: "Use pressure cookers or induction stoves to reduce LPG usage. Check for gas leaks regularly."
                });
            }
            
            if (carCO2 > 1.5) {
                tips.push({
                    priority: 2,
                    text: "Carpool, use public transport, or try walking/cycling for short trips. Maintain your car for better fuel efficiency."
                });
            }
            
            if (flightsCO2 > 1) {
                tips.push({
                    priority: 3,
                    text: "Consider local travel or video calls instead of flights. Offset your flight emissions if possible."
                });
            }
            
            if (dietCO2 > 2) {
                tips.push({
                    priority: 2,
                    text: "Try reducing red meat and dairy. Malaysian cuisine offers many delicious plant-based options!"
                });
            }
            
            if (tips.length === 0) {
                tips.push({
                    priority: 0,
                    text: "You're already doing great! Consider solar panels or supporting local reforestation projects."
                });
            }
            
            tips.sort((a, b) => a.priority - b.priority)
                .forEach(tip => {
                    const li = document.createElement('li');
                    li.textContent = tip.text;
                    tipsList.appendChild(li);
                });
            
            // Show results
            document.getElementById('result').style.display = 'block';
            
            // Scroll to results
            document.getElementById('result').scrollIntoView({ behavior: 'smooth' });

            // After calculation, if user is logged in, save to server
            if (isLoggedIn()) {
                const details = {
                    electricity, lpg, carMileage, carEfficiency, flights, diet, household
                };
                
                fetch('calculator.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'save_calculation=1&footprint=' + encodeURIComponent(totalCO2.toFixed(2)) + '&details=' + encodeURIComponent(JSON.stringify(details))
                })
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        updateHistoryTable(res);
                    } else {
                        console.error('Failed to save calculation:', res);
                    }
                })
                .catch(error => {
                    console.error('Error saving calculation:', error);
                });
            }
        });

        // Modal logic for view/edit
        const historyModal = document.getElementById('historyModal');
        const modalView = document.getElementById('modalView');
        const closeModal = document.getElementById('closeModal');

        if (closeModal) {
            closeModal.onclick = () => { historyModal.style.display = 'none'; };
        }

        window.onclick = (e) => { 
            if (e.target === historyModal) historyModal.style.display = 'none'; 
        };

        // View/Edit/Delete handlers
        function viewBtnHandler() {
            let details = this.getAttribute('data-details');
            try {
                details = JSON.parse(details);
                if (typeof details === 'string') {
                    details = JSON.parse(details);
                }
            } catch (e) { details = {}; }
            
            let html = '<h3>Calculation Details</h3><ul>';
            for (const key in details) {
                html += `<li><b>${key}:</b> ${details[key]}</li>`;
            }
            html += '</ul>';
            modalView.innerHTML = html;
            historyModal.style.display = 'flex';
        }

        function editBtnHandler() {
            const id = this.getAttribute('data-id');
            let details = this.getAttribute('data-details');
            try {
                details = JSON.parse(details);
                if (typeof details === 'string') {
                    details = JSON.parse(details);
                }
            } catch (e) { details = {}; }
            const footprint = this.getAttribute('data-footprint');
            
            let html = `<h3>Edit Calculation</h3><form id='editHistoryForm'><input type='hidden' name='id' value='${id}'>`;
            
            for (const key in details) {
                if (key === 'diet') {
                    html += `<label>Diet:
                        <select name='diet' style="width: 100%; padding: 8px; margin-bottom: 12px;">
                            <option value='meatDaily' ${details[key] === 'meatDaily' ? 'selected' : ''}>Meat Daily</option>
                            <option value='meatWeekly' ${details[key] === 'meatWeekly' ? 'selected' : ''}>Meat Weekly</option>
                            <option value='vegetarian' ${details[key] === 'vegetarian' ? 'selected' : ''}>Vegetarian</option>
                            <option value='vegan' ${details[key] === 'vegan' ? 'selected' : ''}>Vegan</option>
                        </select>
                    </label>`;
                } else {
                    html += `<label>${key.charAt(0).toUpperCase() + key.slice(1)}: <input name='${key}' value='${details[key]}' style="width: 100%; padding: 8px; margin-bottom: 12px;"></label>`;
                }
            }
            
            html += `<label>Footprint (tons): <span id='editFootprintValue' style="font-weight:bold;">${footprint}</span></label>`;
            html += `<button type='submit' style="background: #1F8D49; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">Save Changes</button></form>`;
            
            modalView.innerHTML = html;
            historyModal.style.display = 'flex';

            // Recalculate and update footprint display on input change
            const form = document.getElementById('editHistoryForm');
            const updateFootprint = () => {
                const formData = new FormData(form);
                // Get values (parseFloat for numbers, fallback to 0 or 1 as in calculator)
                const electricity = parseFloat(formData.get('electricity')) || 0;
                const lpg = parseFloat(formData.get('lpg')) || 0;
                const carMileage = parseFloat(formData.get('carMileage')) || 0;
                const carEfficiency = parseFloat(formData.get('carEfficiency')) || 1;
                const flights = parseFloat(formData.get('flights')) || 0;
                const diet = formData.get('diet');
                const household = parseFloat(formData.get('household')) || 1;
                // Malaysian emission factors
                const electricityCO2 = electricity * 0.63 * 12 / 1000;
                const lpgCO2 = lpg * 1.51 * 12 / 1000;
                const carCO2 = (carMileage / carEfficiency) * 2.31 / 1000;
                const flightsCO2 = flights * 0.55;
                let dietCO2 = 0;
                switch(diet) {
                    case 'meatDaily': dietCO2 = 2.8; break;
                    case 'meatWeekly': dietCO2 = 2.0; break;
                    case 'vegetarian': dietCO2 = 1.3; break;
                    case 'vegan': dietCO2 = 1.1; break;
                }
                const totalCO2 = (electricityCO2 + lpgCO2 + carCO2 + flightsCO2 + dietCO2) / household;
                document.getElementById('editFootprintValue').textContent = totalCO2.toFixed(2);
                return totalCO2;
            };
            form.querySelectorAll('input, select').forEach(el => {
                el.addEventListener('input', updateFootprint);
                el.addEventListener('change', updateFootprint);
            });

            form.onsubmit = function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const id = formData.get('id');
                let details = {};
                for (const [k, v] of formData.entries()) {
                    if (k !== 'id') details[k] = v;
                }
                // Recalculate footprint before sending
                const newFootprint = updateFootprint();
                fetch('calculator.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'update_history=1&id=' + encodeURIComponent(id) + '&footprint=' + encodeURIComponent(newFootprint.toFixed(2)) + '&details=' + encodeURIComponent(JSON.stringify(details))
                })
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        // Update table row
                        const row = document.querySelector(`tr[data-id='${id}']`);
                        if (row) {
                            row.querySelector('.footprint-cell').textContent = parseFloat(newFootprint).toFixed(2);
                            row.querySelector('.view-btn').setAttribute('data-details', JSON.stringify(details));
                            row.querySelector('.edit-btn').setAttribute('data-details', JSON.stringify(details));
                            row.querySelector('.edit-btn').setAttribute('data-footprint', parseFloat(newFootprint).toFixed(2));
                        }
                        historyModal.style.display = 'none';
                        showSuccessMessage('Calculation updated successfully!');
                    }
                })
                .catch(error => {
                    console.error('Error updating calculation:', error);
                });
            };
            // Initial update in case values changed
            updateFootprint();
        }

        function deleteBtnHandler() {
            const id = this.getAttribute('data-id');
            if (!confirm('Are you sure you want to delete this calculation?')) return;
            
            fetch('calculator.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'delete_history=1&id=' + encodeURIComponent(id)
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    const row = document.querySelector(`tr[data-id='${id}']`);
                    if (row) {
                        row.style.transition = 'opacity 0.3s ease';
                        row.style.opacity = '0';
                        setTimeout(() => {
                            row.remove();
                            // If no more rows, show the "no history" message
                            const tbody = document.querySelector('#historyTable tbody');
                            if (tbody && tbody.querySelectorAll('tr[data-id]').length === 0) {
                                const noHistoryRow = document.createElement('tr');
                                noHistoryRow.id = 'noHistoryRow';
                                noHistoryRow.innerHTML = '<td colspan="4">No history yet. Calculate to save your first result!</td>';
                                tbody.appendChild(noHistoryRow);
                            }
                        }, 300);
                    }
                    showSuccessMessage('Calculation deleted successfully!');
                }
            })
            .catch(error => {
                console.error('Error deleting calculation:', error);
            });
        }

        // Attach handlers to initial buttons when page loads
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.view-btn').forEach(btn => { btn.onclick = viewBtnHandler; });
            document.querySelectorAll('.edit-btn').forEach(btn => { btn.onclick = editBtnHandler; });
            document.querySelectorAll('.delete-btn').forEach(btn => { btn.onclick = deleteBtnHandler; });
        });
    </script>
</body>
</html>