<?php
// Get saved data from cookies
$memory = isset($_COOKIE['memory']) ? $_COOKIE['memory'] : '';
$history = isset($_COOKIE['history']) ? json_decode($_COOKIE['history'], true) : [];
$wordMode = isset($_COOKIE['word_mode']) ? $_COOKIE['word_mode'] == 'on' : true;

// Variables for display
$display = '';
$expression = '';
$numberResult = '';

// ===== ENGLISH NUMBER TO WORDS =====
function numberToEnglishWords($num) {
    // Round to handle decimals
    $num = round($num, 2);
    
    // Handle negative numbers
    if ($num < 0) {
        return 'negative ' . numberToEnglishWords(abs($num));
    }
    
    // Handle zero
    if ($num == 0) {
        return 'zero';
    }
    
    // Handle decimals
    $whole = floor($num);
    $decimal = round(($num - $whole) * 100);
    
    $words = '';
    
    // Convert whole number part
    if ($whole > 0) {
        $words .= convertWholeNumber($whole);
    }
    
    // Add decimal part if exists
    if ($decimal > 0) {
        if ($whole > 0) {
            $words .= ' point ';
        }
        $words .= convertWholeNumber($decimal);
    }
    
    return trim($words);
}

function convertWholeNumber($num) {
    $units = ['', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen'];
    $tens = ['', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];
    
    if ($num < 20) {
        return $units[$num];
    }
    
    if ($num < 100) {
        $t = floor($num / 10);
        $u = $num % 10;
        if ($u == 0) {
            return $tens[$t];
        }
        return $tens[$t] . '-' . $units[$u];
    }
    
    if ($num < 1000) {
        $h = floor($num / 100);
        $r = $num % 100;
        $result = $units[$h] . ' hundred';
        if ($r > 0) {
            $result .= ' and ' . convertWholeNumber($r);
        }
        return $result;
    }
    
    if ($num < 1000000) {
        $th = floor($num / 1000);
        $r = $num % 1000;
        $result = convertWholeNumber($th) . ' thousand';
        if ($r > 0) {
            $result .= ' ' . convertWholeNumber($r);
        }
        return $result;
    }
    
    if ($num < 1000000000) {
        $mil = floor($num / 1000000);
        $r = $num % 1000000;
        $result = convertWholeNumber($mil) . ' million';
        if ($r > 0) {
            $result .= ' ' . convertWholeNumber($r);
        }
        return $result;
    }
    
    return (string)$num;
}

// ===== Evaluate expression safely =====
function evaluateExpression($expr) {
    // Remove spaces
    $expr = str_replace(' ', '', $expr);
    
    // Handle special functions
    if (strpos($expr, 'square') !== false) {
        $num = str_replace('square', '', $expr);
        if (is_numeric($num)) {
            return $num * $num;
        }
        return null;
    }
    
    if (strpos($expr, 'sqrt') !== false) {
        $num = str_replace('sqrt', '', $expr);
        if (is_numeric($num) && $num >= 0) {
            return sqrt($num);
        }
        return null;
    }
    
    if (strpos($expr, 'factorial') !== false) {
        $num = str_replace('factorial', '', $expr);
        if (is_numeric($num) && $num >= 0 && $num <= 20) {
            $result = 1;
            for ($i = 2; $i <= $num; $i++) {
                $result *= $i;
            }
            return $result;
        }
        return null;
    }
    
    // Regular expression - clean it up
    $expr = str_replace(['×', '÷', '−'], ['*', '/', '-'], $expr);
    
    // Only allow safe characters
    $expr = preg_replace('/[^0-9+\-*\/().%]/', '', $expr);
    
    // Handle percentage
    if (strpos($expr, '%') !== false) {
        $expr = str_replace('%', '/100', $expr);
    }
    
    // Evaluate
    try {
        $result = eval("return $expr;");
        return $result;
    } catch (Exception $e) {
        return null;
    }
}

// ===== HANDLE FORM SUBMISSION =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $display = isset($_POST['display']) ? $_POST['display'] : '';
    $expression = isset($_POST['expression']) ? $_POST['expression'] : '';
    $memory = isset($_POST['memory']) ? $_POST['memory'] : '';
    
    if (isset($_POST['button'])) {
        $button = $_POST['button'];
        
        // ===== CLEAR =====
        if ($button == 'C') {
            $display = '';
            $expression = '';
            $numberResult = '';
        }
        
        // ===== EQUALS =====
        elseif ($button == '=') {
            if (!empty($expression)) {
                // Evaluate the expression
                $result = evaluateExpression($expression);
                
                if ($result !== null) {
                    // Convert result to English words
                    $display = numberToEnglishWords($result);
                    $numberResult = $expression . ' = ' . $result;
                    
                    // Save to history
                    $history[] = [
                        'expression' => $expression,
                        'result' => $display,
                        'number' => $numberResult
                    ];
                    
                    if (count($history) > 10) {
                        array_shift($history);
                    }
                    
                    setcookie('history', json_encode($history), time() + 86400 * 30, '/');
                } else {
                    $display = 'Error';
                }
            }
        }
        
        // ===== TOGGLE MODE =====
        elseif ($button == 'toggle') {
            $wordMode = !$wordMode;
            setcookie('word_mode', $wordMode ? 'on' : 'off', time() + 86400 * 30, '/');
        }
        
        // ===== MEMORY FUNCTIONS =====
        elseif ($button == 'MC') {
            $memory = '';
            setcookie('memory', '', time() - 3600, '/');
        }
        
        elseif ($button == 'MR') {
            if ($memory !== '') {
                $display = $memory;
                $expression = $memory;
            }
        }
        
        elseif ($button == 'MS') {
            if ($display !== '') {
                $memory = $display;
                setcookie('memory', $memory, time() + 86400 * 30, '/');
            }
        }
        
        elseif ($button == 'M+') {
            if ($memory !== '' && $display !== '') {
                $memory = $memory . ' + ' . $display;
                setcookie('memory', $memory, time() + 86400 * 30, '/');
            }
        }
        
        elseif ($button == 'clear_history') {
            $history = [];
            setcookie('history', '', time() - 3600, '/');
        }
        
        // ===== SPECIAL FUNCTIONS =====
        elseif ($button == 'square') {
            $expression .= 'square';
        }
        elseif ($button == 'sqrt') {
            $expression .= 'sqrt';
        }
        elseif ($button == 'factorial') {
            $expression .= 'factorial';
        }
        elseif ($button == 'percent') {
            $expression .= '%';
        }
        else {
            $expression .= $button;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Word Calculator</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(145deg, #2b3a67, #1d2a4a);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .container {
            background: #f5f7fc;
            padding: 30px;
            border-radius: 28px;
            width: 460px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.5);
        }
        h2 {
            text-align: center;
            color: #1d2a4a;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .sub {
            text-align: center;
            color: #6b7a9f;
            font-size: 14px;
            margin-top: -6px;
            margin-bottom: 18px;
        }
        .display {
            background: #0f172a;
            color: white;
            padding: 20px 22px;
            border-radius: 18px;
            margin-bottom: 22px;
            min-height: 130px;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            align-items: flex-start;
        }
        .display .expr {
            font-size: 16px;
            color: #94a3b8;
            min-height: 26px;
            width: 100%;
            text-align: left;
            border-bottom: 1px dashed #334155;
            padding-bottom: 6px;
        }
        .display .num-result {
            font-size: 15px;
            color: #a5b4d0;
            min-height: 24px;
            width: 100%;
            text-align: left;
            margin-top: 6px;
        }
        .display .word-result {
            font-size: 26px;
            font-weight: 600;
            color: #b9e6b9;
            min-height: 50px;
            word-wrap: break-word;
            width: 100%;
            text-align: left;
            line-height: 1.3;
            margin-top: 8px;
        }
        .display .memory-tag {
            font-size: 12px;
            color: #fbbf24;
            margin-top: 6px;
            width: 100%;
            text-align: left;
        }
        .btn-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }
        .btn {
            padding: 16px 0;
            font-size: 22px;
            font-weight: 600;
            border: none;
            border-radius: 16px;
            cursor: pointer;
            transition: 0.15s;
            background: #e9edf6;
            color: #1e293b;
            box-shadow: 0 4px 0 #cbd5e1;
        }
        .btn:active {
            transform: translateY(3px);
            box-shadow: 0 1px 0 #cbd5e1;
        }
        .btn.op {
            background: #3b4f8c;
            color: white;
            box-shadow: 0 4px 0 #1e2f5a;
        }
        .btn.op:active {
            box-shadow: 0 1px 0 #1e2f5a;
        }
        .btn.eq {
            background: #1e7e34;
            color: white;
            box-shadow: 0 4px 0 #0f5622;
            grid-column: span 2;
        }
        .btn.eq:active {
            box-shadow: 0 1px 0 #0f5622;
        }
        .btn.clr {
            background: #b91c1c;
            color: white;
            box-shadow: 0 4px 0 #7f1d1d;
        }
        .btn.clr:active {
            box-shadow: 0 1px 0 #7f1d1d;
        }
        .btn.sp {
            background: #b45309;
            color: white;
            box-shadow: 0 4px 0 #7b341e;
        }
        .btn.sp:active {
            box-shadow: 0 1px 0 #7b341e;
        }
        .btn.toggle {
            background: #6d28d9;
            color: white;
            box-shadow: 0 4px 0 #4c1d95;
            font-size: 18px;
        }
        .btn.toggle:active {
            box-shadow: 0 1px 0 #4c1d95;
        }
        .mem-section {
            margin-top: 22px;
            background: #eef2f9;
            padding: 14px 16px;
            border-radius: 18px;
        }
        .mem-section strong {
            color: #1d2a4a;
        }
        .mem-btns {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-top: 10px;
        }
        .mem-btn {
            background: #d4dcec;
            border: none;
            padding: 10px 0;
            border-radius: 30px;
            font-weight: 600;
            color: #1d2a4a;
            font-size: 14px;
            cursor: pointer;
            transition: 0.1s;
        }
        .mem-btn:active {
            background: #bcc7df;
            transform: scale(0.96);
        }
        .history-box {
            margin-top: 22px;
            background: #eef2f9;
            padding: 14px 16px;
            border-radius: 18px;
            max-height: 160px;
            overflow-y: auto;
        }
        .history-box strong {
            color: #1d2a4a;
        }
        .history-item {
            padding: 6px 0;
            border-bottom: 1px solid #d7dff0;
            font-size: 14px;
            display: flex;
            justify-content: space-between;
        }
        .history-item:last-child {
            border-bottom: none;
        }
        .history-item .h-result {
            font-weight: 600;
            color: #1e7e34;
        }
        .clear-history-btn {
            background: #b91c1c;
            color: white;
            border: none;
            padding: 8px 0;
            border-radius: 40px;
            font-weight: 600;
            width: 100%;
            margin-top: 12px;
            cursor: pointer;
            font-size: 14px;
            transition: 0.1s;
        }
        .clear-history-btn:active {
            background: #7f1d1d;
            transform: scale(0.97);
        }
        .mode-note {
            text-align: center;
            margin-top: 16px;
            font-size: 13px;
            color: #4a5b7c;
            background: #e2e8f5;
            padding: 10px;
            border-radius: 40px;
        }
        .mode-note span {
            font-weight: 600;
            color: #1d2a4a;
        }
        .footer {
            font-size: 12px;
            text-align: center;
            color: #6b7a9f;
            margin-top: 12px;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>🧮 Word Calculator</h2>
    <div class="sub">numbers → English words (result only)</div>

    <!-- DISPLAY -->
    <div class="display">
        <div class="expr"><?php echo htmlspecialchars($expression); ?></div>
        <div class="num-result"><?php echo $numberResult !== '' ? '📝 ' . htmlspecialchars($numberResult) : ''; ?></div>
        <div class="word-result"><?php echo htmlspecialchars($display); ?></div>
        <?php if ($memory !== ''): ?>
            <div class="memory-tag">💾 M = <?php echo htmlspecialchars($memory); ?></div>
        <?php endif; ?>
    </div>

    <form method="POST">
        <input type="hidden" name="display" value="<?php echo htmlspecialchars($display); ?>">
        <input type="hidden" name="expression" value="<?php echo htmlspecialchars($expression); ?>">
        <input type="hidden" name="memory" value="<?php echo htmlspecialchars($memory); ?>">

        <!-- BUTTONS -->
        <div class="btn-grid">
            <button class="btn sp" name="button" value="factorial">n!</button>
            <button class="btn sp" name="button" value="sqrt">√</button>
            <button class="btn sp" name="button" value="square">x²</button>
            <button class="btn toggle" name="button" value="toggle">🔤</button>

            <button class="btn" name="button" value="7">7</button>
            <button class="btn" name="button" value="8">8</button>
            <button class="btn" name="button" value="9">9</button>
            <button class="btn op" name="button" value="/">÷</button>

            <button class="btn" name="button" value="4">4</button>
            <button class="btn" name="button" value="5">5</button>
            <button class="btn" name="button" value="6">6</button>
            <button class="btn op" name="button" value="*">×</button>

            <button class="btn" name="button" value="1">1</button>
            <button class="btn" name="button" value="2">2</button>
            <button class="btn" name="button" value="3">3</button>
            <button class="btn op" name="button" value="-">−</button>

            <button class="btn" name="button" value="0">0</button>
            <button class="btn" name="button" value=".">.</button>
            <button class="btn sp" name="button" value="percent">%</button>
            <button class="btn op" name="button" value="+">+</button>

            <button class="btn clr" name="button" value="C">C</button>
            <button class="btn op" name="button" value="(">(</button>
            <button class="btn op" name="button" value=")">)</button>
            <button class="btn eq" name="button" value="=">=</button>
        </div>

        <!-- MEMORY -->
        <div class="mem-section">
            <strong>💾 Memory</strong>
            <div class="mem-btns">
                <button class="mem-btn" name="button" value="MC">MC</button>
                <button class="mem-btn" name="button" value="MR">MR</button>
                <button class="mem-btn" name="button" value="MS">MS</button>
                <button class="mem-btn" name="button" value="M+">M+</button>
            </div>
        </div>
    </form>

    <!-- HISTORY -->
    <div class="history-box">
        <strong>📜 History</strong>
        <?php if (empty($history)): ?>
            <p style="color:#6b7a9f; font-size:14px; margin-top:6px;">No calculations yet</p>
        <?php else: ?>
            <?php foreach (array_reverse($history) as $item): ?>
                <div class="history-item">
                    <span><?php echo htmlspecialchars($item['expression']); ?></span>
                    <span class="h-result">= <?php echo htmlspecialchars($item['result']); ?></span>
                </div>
            <?php endforeach; ?>
            <form method="POST">
                <input type="hidden" name="display" value="<?php echo htmlspecialchars($display); ?>">
                <input type="hidden" name="expression" value="<?php echo htmlspecialchars($expression); ?>">
                <input type="hidden" name="memory" value="<?php echo htmlspecialchars($memory); ?>">
                <button class="clear-history-btn" name="button" value="clear_history">🗑️ Clear History</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="mode-note">
        💡 <span>Result → English words</span>  (operators stay as symbols)
    </div>
    <div class="footer">simple • only result becomes word</div>
</div>
</body>
</html>
