<?php
// ===== CUSTOM WORD CALCULATOR =====

// Get saved data from cookies
$memory = isset($_COOKIE['memory']) ? $_COOKIE['memory'] : '';
$history = isset($_COOKIE['history']) ? json_decode($_COOKIE['history'], true) : [];
$wordMode = isset($_COOKIE['word_mode']) ? $_COOKIE['word_mode'] == 'on' : true;

// Variables for display
$display = '';
$expression = '';
$numberResult = '';

// ===== CUSTOM NUMBER TO WORDS MAPPING =====
// Each number becomes a specific word or phrase!
function numberToWords($num) {
    // Round to handle decimals
    $num = round($num, 2);
    
    // Check if it's a whole number or has decimals
    $isDecimal = ($num != floor($num));
    $wholePart = floor($num);
    $decimalPart = round(($num - $wholePart) * 100);
    
    // CUSTOM MAPPING: Each number maps to a specific word/phrase
    $customWords = [
        0 => 'Zero 😊',
        1 => 'gcash sa',
        2 => 'tantan',
        3 => 'you',
        4 => 'ambot',
        5 => 'chawchaw',
        6 => 'a',
        7 => 'gamit2 pud ug utok',
        8 => 'lol',
        9 => 'gamit2 pud ug utok',
        10 => 'nag calcu pa ka',
        11 => 'one love',
        12 => 'kulot',
        13 => 'bad luck',
        14 => 'forever young',
        15 => 'ngi',
        16 => 'never forget',
        17 => 'beautiful day',
        18 => 'eternal love',
        19 => 'gamit2 pud ug utok',
        20 => 'gcash sa',
        21 => 'special',
        22 => 'gamit2 pud ug utok',
        23 => 'wonderful',
        24 => 'magical',
        25 => 'ambot',
        26 => 'incredible',
        27 => 'unstoppable',
        28 => 'powerful',
        29 => 'beautiful',
        30 => 'fantastic',
        40 => 'fabulous',
        50 => 'legendary',
        60 => 'ambot',
        70 => 'wow',
        80 => 'migo',
        90 => 'phenomenal',
        100 => 'perfect ten',
        200 => 'double trouble',
        300 => 'triple threat',
        400 => 'four seasons',
        500 => 'fifty shades',
        1000 => 'thousand dreams',
        1000000 => 'calcu pa ka'
    ];
    
    // Handle negative numbers
    if ($num < 0) {
        return 'negative ' . numberToWords(abs($num));
    }
    
    // Handle zero
    if ($num == 0) {
        return 'Zero 😊';
    }
    
    // Handle decimal numbers
    if ($isDecimal) {
        $whole = numberToWords($wholePart);
        $decimal = '';
        
        // Handle decimal part
        if ($decimalPart > 0) {
            $decimal = ' point ';
            if ($decimalPart < 21) {
                $decimal .= isset($customWords[$decimalPart]) ? $customWords[$decimalPart] : $decimalPart;
            } else {
                $tens = floor($decimalPart / 10) * 10;
                $ones = $decimalPart % 10;
                $decimal .= isset($customWords[$tens]) ? $customWords[$tens] : $tens;
                if ($ones > 0) {
                    $decimal .= '-' . (isset($customWords[$ones]) ? $customWords[$ones] : $ones);
                }
            }
        }
        
        return $whole . $decimal;
    }
    
    // Check if number exists in custom mapping
    if (isset($customWords[$num])) {
        return $customWords[$num];
    }
    
    // For numbers not in mapping, break them down
    if ($num < 100) {
        $tens = floor($num / 10) * 10;
        $ones = $num % 10;
        
        $result = '';
        if ($tens > 0 && isset($customWords[$tens])) {
            $result .= $customWords[$tens];
        } elseif ($tens > 0) {
            $result .= $tens;
        }
        
        if ($ones > 0) {
            if ($tens > 0) $result .= '-';
            $result .= isset($customWords[$ones]) ? $customWords[$ones] : $ones;
        }
        
        return $result;
    }
    
    if ($num < 1000) {
        $hundreds = floor($num / 100);
        $remainder = $num % 100;
        
        $result = '';
        if ($hundreds > 0) {
            $result .= isset($customWords[$hundreds]) ? $customWords[$hundreds] : $hundreds;
            $result .= ' hundred';
        }
        
        if ($remainder > 0) {
            if ($hundreds > 0) $result .= ' ';
            $result .= numberToWords($remainder);
        }
        
        return $result;
    }
    
    // For bigger numbers, show with custom words
    if ($num < 1000000) {
        $thousands = floor($num / 1000);
        $remainder = $num % 1000;
        
        $result = '';
        if ($thousands > 0) {
            $result .= numberToWords($thousands);
            $result .= ' thousand';
        }
        
        if ($remainder > 0) {
            if ($thousands > 0) $result .= ' ';
            $result .= numberToWords($remainder);
        }
        
        return $result;
    }
    
    // Default fallback
    return number_format($num, 0, '.', ',');
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
                // Copy expression for calculation
                $calc = $expression;
                
                // Remove spaces
                $calc = str_replace(' ', '', $calc);
                
                // Handle factorial
                if (preg_match('/(\d+)!/', $calc, $matches)) {
                    $fact = 1;
                    for ($i = 1; $i <= $matches[1]; $i++) {
                        $fact *= $i;
                    }
                    $calc = str_replace($matches[0], $fact, $calc);
                }
                
                // Handle square root
                $calc = str_replace('√', 'sqrt', $calc);
                
                // Handle percentage
                $calc = str_replace('%', '/100', $calc);
                
                // Handle power
                $calc = str_replace('^', '**', $calc);
                
                // Calculate the result
                try {
                    $result = eval("return $calc;");
                    
                    if (is_numeric($result)) {
                        $numberResult = $result;
                        
                        // Convert to custom words
                        $display = numberToWords($result);
                        
                        // Save to history
                        $history[] = [
                            'expression' => $expression,
                            'result' => $display,
                            'number' => $result
                        ];
                        
                        // Keep only last 10
                        if (count($history) > 10) {
                            array_shift($history);
                        }
                        
                        // Save history cookie
                        setcookie('history', json_encode($history), time() + 86400 * 30, '/');
                    }
                } catch (Exception $e) {
                    $display = 'Error';
                }
            }
        }
        
        // ===== TOGGLE MODE =====
        elseif ($button == 'toggle') {
            $wordMode = !$wordMode;
            setcookie('word_mode', $wordMode ? 'on' : 'off', time() + 86400 * 30, '/');
        }
        
        // ===== MEMORY CLEAR =====
        elseif ($button == 'MC') {
            $memory = '';
            setcookie('memory', '', time() - 3600, '/');
        }
        
        // ===== MEMORY RECALL =====
        elseif ($button == 'MR') {
            if ($memory !== '') {
                $display = $memory;
                $expression = $memory;
            }
        }
        
        // ===== MEMORY STORE =====
        elseif ($button == 'MS') {
            if (is_numeric($display)) {
                $memory = $display;
                setcookie('memory', $memory, time() + 86400 * 30, '/');
            }
        }
        
        // ===== MEMORY ADD =====
        elseif ($button == 'M+') {
            if ($memory !== '' && is_numeric($display)) {
                $memory = (float)$memory + (float)$display;
                setcookie('memory', $memory, time() + 86400 * 30, '/');
            }
        }
        
        // ===== CLEAR HISTORY =====
        elseif ($button == 'clear_history') {
            $history = [];
            setcookie('history', '', time() - 3600, '/');
        }
        
        // ===== ALL OTHER BUTTONS =====
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
    <title>Custom Word Calculator</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .container {
            background: white;
            padding: 30px;
            border-radius: 20px;
            width: 450px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }
        
        .display {
            background: #1a1a2e;
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            min-height: 120px;
            text-align: right;
        }
        
        .display .expression {
            font-size: 18px;
            color: #888;
            min-height: 25px;
        }
        
        .display .result {
            font-size: 28px;
            font-weight: bold;
            color: #f5576c;
            min-height: 60px;
            word-wrap: break-word;
        }
        
        .display .number-display {
            font-size: 14px;
            color: #666;
            min-height: 20px;
        }
        
        .display .memory-label {
            text-align: left;
            font-size: 12px;
            color: #4CAF50;
        }
        
        .buttons {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }
        
        .btn {
            padding: 18px;
            font-size: 20px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: bold;
            transition: 0.2s;
        }
        
        .btn:hover {
            transform: scale(1.05);
        }
        
        .btn-number {
            background: #f0f0f0;
        }
        
        .btn-operator {
            background: #667eea;
            color: white;
        }
        
        .btn-equals {
            background: #f5576c;
            color: white;
            grid-column: span 2;
        }
        
        .btn-clear {
            background: #f44336;
            color: white;
        }
        
        .btn-special {
            background: #ff9800;
            color: white;
        }
        
        .btn-toggle {
            background: #9c27b0;
            color: white;
        }
        
        .btn-memory {
            background: #6c757d;
            color: white;
            padding: 12px;
            font-size: 14px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
        }
        
        .btn-memory:hover {
            background: #5a6268;
        }
        
        .memory-section {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .memory-buttons {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-top: 10px;
        }
        
        .history {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            max-height: 150px;
            overflow-y: auto;
        }
        
        .history-item {
            padding: 5px 0;
            border-bottom: 1px solid #ddd;
            font-size: 14px;
        }
        
        .mode-text {
            text-align: center;
            margin-top: 10px;
            font-size: 12px;
            color: #666;
        }
        
        .custom-word {
            color: #f5576c;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>💝 Custom Word Calculator</h2>
        
        <!-- Display -->
        <div class="display">
            <div class="expression"><?php echo htmlspecialchars($expression); ?></div>
            <div class="number-display"><?php echo $numberResult !== '' ? '# ' . htmlspecialchars($numberResult) : ''; ?></div>
            <div class="result"><?php echo htmlspecialchars($display); ?></div>
            <?php if ($memory !== ''): ?>
                <div class="memory-label">M = <?php echo htmlspecialchars($memory); ?></div>
            <?php endif; ?>
        </div>
        
        <!-- Buttons -->
        <form method="POST">
            <input type="hidden" name="display" value="<?php echo htmlspecialchars($display); ?>">
            <input type="hidden" name="expression" value="<?php echo htmlspecialchars($expression); ?>">
            <input type="hidden" name="memory" value="<?php echo htmlspecialchars($memory); ?>">
            
            <div class="buttons">
                <!-- Row 1 -->
                <button type="submit" class="btn btn-special" name="button" value="factorial">n!</button>
                <button type="submit" class="btn btn-special" name="button" value="sqrt">√</button>
                <button type="submit" class="btn btn-special" name="button" value="square">x²</button>
                <button type="submit" class="btn btn-toggle" name="button" value="toggle">🔤</button>
                
                <!-- Row 2 -->
                <button type="submit" class="btn btn-number" name="button" value="7">7</button>
                <button type="submit" class="btn btn-number" name="button" value="8">8</button>
                <button type="submit" class="btn btn-number" name="button" value="9">9</button>
                <button type="submit" class="btn btn-operator" name="button" value="/">÷</button>
                
                <!-- Row 3 -->
                <button type="submit" class="btn btn-number" name="button" value="4">4</button>
                <button type="submit" class="btn btn-number" name="button" value="5">5</button>
                <button type="submit" class="btn btn-number" name="button" value="6">6</button>
                <button type="submit" class="btn btn-operator" name="button" value="*">×</button>
                
                <!-- Row 4 -->
                <button type="submit" class="btn btn-number" name="button" value="1">1</button>
                <button type="submit" class="btn btn-number" name="button" value="2">2</button>
                <button type="submit" class="btn btn-number" name="button" value="3">3</button>
                <button type="submit" class="btn btn-operator" name="button" value="-">−</button>
                
                <!-- Row 5 -->
                <button type="submit" class="btn btn-number" name="button" value="0">0</button>
                <button type="submit" class="btn btn-number" name="button" value=".">.</button>
                <button type="submit" class="btn btn-special" name="button" value="percent">%</button>
                <button type="submit" class="btn btn-operator" name="button" value="+">+</button>
                
                <!-- Row 6 -->
                <button type="submit" class="btn btn-clear" name="button" value="C">C</button>
                <button type="submit" class="btn btn-operator" name="button" value="(">(</button>
                <button type="submit" class="btn btn-operator" name="button" value=")">)</button>
                <button type="submit" class="btn btn-equals" name="button" value="=">=</button>
            </div>
            
            <!-- Memory -->
            <div class="memory-section">
                <strong>💾 Memory</strong>
                <div class="memory-buttons">
                    <button type="submit" class="btn-memory" name="button" value="MC">MC</button>
                    <button type="submit" class="btn-memory" name="button" value="MR">MR</button>
                    <button type="submit" class="btn-memory" name="button" value="MS">MS</button>
                    <button type="submit" class="btn-memory" name="button" value="M+">M+</button>
                </div>
            </div>
        </form>
        
        <!-- History -->
        <div class="history">
            <strong>📜 History</strong>
            <?php if (empty($history)): ?>
                <p style="color:#999; font-size:14px;">No calculations yet</p>
            <?php else: ?>
                <?php foreach (array_reverse($history) as $item): ?>
                    <div class="history-item">
                        <?php echo htmlspecialchars($item['expression']); ?> = 
                        <span style="color:#f5576c;"><?php echo htmlspecialchars($item['result']); ?></span>
                        <span style="color:#999; font-size:12px;">(<?php echo $item['number']; ?>)</span>
                    </div>
                <?php endforeach; ?>
                <form method="POST">
                    <input type="hidden" name="display" value="<?php echo htmlspecialchars($display); ?>">
                    <input type="hidden" name="expression" value="<?php echo htmlspecialchars($expression); ?>">
                    <input type="hidden" name="memory" value="<?php echo htmlspecialchars($memory); ?>">
                    <button type="submit" class="btn-clear" name="button" value="clear_history" style="padding:8px; font-size:12px; width:100%; border:none; border-radius:5px; cursor:pointer; margin-top:10px;">
                        Clear History
                    </button>
                </form>
            <?php endif; ?>
        </div>
        
        <div class="mode-text">
            💝 Numbers become custom words! Try 1+2 = "I hate" 😄
        </div>
    </div>
</body>
</html>