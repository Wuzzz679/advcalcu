<?php

// Get saved data from cookies
$memory = isset($_COOKIE['memory']) ? $_COOKIE['memory'] : '';
$history = isset($_COOKIE['history']) ? json_decode($_COOKIE['history'], true) : [];
$wordMode = isset($_COOKIE['word_mode']) ? $_COOKIE['word_mode'] == 'on' : true;

// Variables for display
$display = '';
$expression = '';
$numberResult = '';

// ===== BISAYA CUSTOM WORD MAPPING =====
$customWords = [
    0 => 'Zero 😊',
    1 => 'ako',
    2 => 'naglagot',
    3 => 'nimo',
    4 => 'hangtod sa hangtod',
    5 => 'ug hangtod',
    6 => 'kanunay',
    7 => 'aking',
    8 => 'walay katapusan',
    9 => 'gihigugma tika',
    10 => 'pinangga',
    11 => 'usa ka gugma',
    12 => 'tam-is nga damgo',
    13 => 'malas',
    14 => 'batan-on hangtod sa hangtod',
    15 => 'sweet sixteen',
    16 => 'dili gyud makalimot',
    17 => 'nindot nga adlaw',
    18 => 'walay katapusan nga gugma',
    19 => 'maayong gabii',
    20 => 'perpekto',
    21 => 'espesyal',
    22 => 'katingalahan',
    23 => 'makalilisang',
    24 => 'makahihimalat',
    25 => 'gwapa/gwapo',
    26 => 'dili katuohan',
    27 => 'dili mapugngan',
    28 => 'gamhanan',
    29 => 'matahum',
    30 => 'fantastiko',
    40 => 'kabibo',
    50 => 'maalamat',
    60 => 'labing maayo',
    70 => 'makapahingangha',
    80 => 'hayag',
    90 => 'talagsaon',
    100 => 'perpekto nga napulo',
    200 => 'doble nga problema',
    300 => 'triple nga hulga',
    400 => 'upat ka panahon',
    500 => 'kalim-an ka landong',
    1000 => 'libo ka damgo',
    1000000 => 'milyon nga salamangka'
];

// ===== FUNCTION: Convert number to Bisaya words =====
function numberToWords($num, $customWords) {
    // Round to handle decimals
    $num = round($num, 2);
    
    // Handle negative numbers
    if ($num < 0) {
        return 'negatibo ' . numberToWords(abs($num), $customWords);
    }
    
    // Handle zero
    if ($num == 0) {
        return 'Zero 😊';
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
            $result .= ' ka gatos';
        }
        
        if ($remainder > 0) {
            if ($hundreds > 0) $result .= ' ';
            $result .= numberToWords($remainder, $customWords);
        }
        
        return $result;
    }
    
    if ($num < 1000000) {
        $thousands = floor($num / 1000);
        $remainder = $num % 1000;
        
        $result = '';
        if ($thousands > 0) {
            $result .= numberToWords($thousands, $customWords);
            $result .= ' ka libo';
        }
        
        if ($remainder > 0) {
            if ($thousands > 0) $result .= ' ';
            $result .= numberToWords($remainder, $customWords);
        }
        
        return $result;
    }
    
    return number_format($num, 0, '.', ',');
}

// ===== Convert expression to Bisaya words =====
function calculateStringResult($expr, $customWords) {
    // Remove spaces
    $expr = str_replace(' ', '', $expr);
    
    // Replace numbers with their custom words
    $result = '';
    $currentNumber = '';
    
    for ($i = 0; $i < strlen($expr); $i++) {
        $char = $expr[$i];
        
        if (is_numeric($char) || $char == '.') {
            $currentNumber .= $char;
        } else {
            if ($currentNumber !== '') {
                $num = (float)$currentNumber;
                if (isset($customWords[$num])) {
                    $result .= $customWords[$num];
                } else {
                    $result .= numberToWords($num, $customWords);
                }
                $currentNumber = '';
            }
            
            // Add operator as Bisaya word
            switch ($char) {
                case '+':
                    $result .= ' dugang ';
                    break;
                case '-':
                    $result .= ' minus ';
                    break;
                case '*':
                    $result .= ' ka times ';
                    break;
                case '/':
                    $result .= ' bahin sa ';
                    break;
                case '^':
                    $result .= ' gipataas sa ';
                    break;
                case '!':
                    $result .= ' factorial ';
                    break;
                case '√':
                    $result .= ' square root sa ';
                    break;
                case '%':
                    $result .= ' porsyento ';
                    break;
                case '(':
                    $result .= ' ( ';
                    break;
                case ')':
                    $result .= ' ) ';
                    break;
                default:
                    $result .= $char . ' ';
                    break;
            }
        }
    }
    
    if ($currentNumber !== '') {
        $num = (float)$currentNumber;
        if (isset($customWords[$num])) {
            $result .= $customWords[$num];
        } else {
            $result .= numberToWords($num, $customWords);
        }
    }
    
    return trim($result);
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
                // Convert expression to Bisaya words
                $display = calculateStringResult($expression, $customWords);
                $numberResult = $expression;
                
                // Save to history
                $history[] = [
                    'expression' => $expression,
                    'result' => $display,
                    'number' => $expression . ' = ' . $display
                ];
                
                if (count($history) > 10) {
                    array_shift($history);
                }
                
                setcookie('history', json_encode($history), time() + 86400 * 30, '/');
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
    <title>Bisaya Word Calculator</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            font-size: 24px;
        }
        
        .subtitle {
            text-align: center;
            color: #888;
            font-size: 14px;
            margin-top: -15px;
            margin-bottom: 20px;
        }
        
        .display {
            background: #1a1a2e;
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            min-height: 130px;
            text-align: right;
        }
        
        .display .expression {
            font-size: 18px;
            color: #888;
            min-height: 25px;
            text-align: left;
        }
        
        .display .result {
            font-size: 24px;
            font-weight: bold;
            color: #4CAF50;
            min-height: 60px;
            word-wrap: break-word;
            text-align: left;
            line-height: 1.4;
        }
        
        .display .number-display {
            font-size: 14px;
            color: #666;
            min-height: 20px;
            text-align: left;
        }
        
        .display .memory-label {
            text-align: left;
            font-size: 12px;
            color: #ff9800;
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
        
        .btn:active {
            transform: scale(0.95);
        }
        
        .btn-number {
            background: #f0f0f0;
            color: #333;
        }
        
        .btn-number:hover {
            background: #e0e0e0;
        }
        
        .btn-operator {
            background: #667eea;
            color: white;
        }
        
        .btn-operator:hover {
            background: #5a67d8;
        }
        
        .btn-equals {
            background: #4CAF50;
            color: white;
            grid-column: span 2;
        }
        
        .btn-equals:hover {
            background: #45a049;
        }
        
        .btn-clear {
            background: #f44336;
            color: white;
        }
        
        .btn-clear:hover {
            background: #da190b;
        }
        
        .btn-special {
            background: #ff9800;
            color: white;
        }
        
        .btn-special:hover {
            background: #e68900;
        }
        
        .btn-toggle {
            background: #9c27b0;
            color: white;
        }
        
        .btn-toggle:hover {
            background: #7b1fa2;
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
        
        .memory-section strong {
            color: #333;
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
        
        .history strong {
            color: #333;
        }
        
        .history-item {
            padding: 5px 0;
            border-bottom: 1px solid #ddd;
            font-size: 14px;
        }
        
        .history-item:last-child {
            border-bottom: none;
        }
        
        .mode-text {
            text-align: center;
            margin-top: 10px;
            font-size: 12px;
            color: #888;
            background: #f0f0f0;
            padding: 8px;
            border-radius: 5px;
        }
        
        .mode-text span {
            color: #4CAF50;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>💝 Bisaya Calculator</h2>
        <div class="subtitle">🇵🇭 Numero → Bisaya nga Pulong</div>
        
        <!-- Display -->
        <div class="display">
            <div class="expression"><?php echo htmlspecialchars($expression); ?></div>
            <div class="number-display"><?php echo $numberResult !== '' ? '📝 ' . htmlspecialchars($numberResult) : ''; ?></div>
            <div class="result"><?php echo htmlspecialchars($display); ?></div>
            <?php if ($memory !== ''): ?>
                <div class="memory-label">💾 M = <?php echo htmlspecialchars($memory); ?></div>
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
            <strong>📜 Kasaysayan</strong>
            <?php if (empty($history)): ?>
                <p style="color:#999; font-size:14px;">Wala pay kalkulasyon</p>
            <?php else: ?>
                <?php foreach (array_reverse($history) as $item): ?>
                    <div class="history-item">
                        <?php echo htmlspecialchars($item['expression']); ?> = 
                        <span style="color:#4CAF50; font-weight:bold;"><?php echo htmlspecialchars($item['result']); ?></span>
                    </div>
                <?php endforeach; ?>
                <form method="POST">
                    <input type="hidden" name="display" value="<?php echo htmlspecialchars($display); ?>">
                    <input type="hidden" name="expression" value="<?php echo htmlspecialchars($expression); ?>">
                    <input type="hidden" name="memory" value="<?php echo htmlspecialchars($memory); ?>">
                    <button type="submit" class="btn-clear" name="button" value="clear_history" style="padding:8px; font-size:12px; width:100%; border:none; border-radius:5px; cursor:pointer; margin-top:10px; background:#f44336; color:white;">
                        🗑️ Pagtangtang sa Kasaysayan
                    </button>
                </form>
            <?php endif; ?>
        </div>
        
        <div class="mode-text">
            💡 Ang mga numero mahimong <span>Bisaya nga mga pulong</span>!<br>
            Sulayi: 1 + 2 = "ako dugang naglagot"
        </div>
    </div>
</body>
</html>
