<?php
session_start();
require_once 'data.php';

$rows = 20;
$cols = 20;

// セッションの初期化
$error_message = '';
if (isset($_SESSION['error'])) {
  $error_message = $_SESSION['error'];
  unset($_SESSION['error']);
}
if (!isset($_SESSION['money'])) {
  $_SESSION['money'] = 1000;
}
if (!isset($_SESSION['rank'])) {
  $_SESSION['rank'] = 1;
}
if (!isset($_SESSION['turns'])) {
  $_SESSION['turns'] = 0;
}
if (!isset($_SESSION['inventory'])) {
  $_SESSION['inventory'] = [];
}
if (!isset($_SESSION['farm'])) {
  $_SESSION['farm'] = array_fill(0, $rows, array_fill(0, $cols, ['type'=>'grass', 'crop'=>null, 'planted_at'=>null]));
}
if (!isset($_SESSION['last_action'])) {
  $_SESSION['last_action'] = 'field';
}

// 操作の処理
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $action = isset($_POST['action']) ? $_POST['action'] : null;
  // 前回の操作を保存して次回の初期値にする
  if ($action) {
    $_SESSION['last_action'] = $action;
  }

  // 前回のフィールドの位置を取得
  $cells = isset($_POST['cells']) ? $_POST['cells'] : [];
  $selected_cell_count = 0;
  $max_row = null;
  $max_col = null;
  if (!empty($cells)) {
    foreach ($cells as $row => $row_data) {
      $selected_cell_count += count($row_data);
      $row = (int)$row;
      if ($max_row === null || $row > $max_row) {
        $max_row = $row;
      }
      if (!empty($row_data)) {
        $row_max_col = max(array_map('intval', array_keys($row_data)));
        if ($max_col === null || $row_max_col > $max_col) {
          $max_col = $row_max_col;
        }
      }
    }
  }

  // 操作の実行
  if ($action === 'wait') {
    // 操作「待つ」
    $_SESSION['turns']++;
  } elseif ($selected_cell_count > $_SESSION['rank']) {
    // ランク以上のセルを選択した場合はエラー
    $_SESSION['error'] = "一度に変更できるのはランクと同じ数（{$_SESSION['rank']}個）までです。";
  } elseif ($action && !empty($cells) && isset($tile_types[$action])) {
    $tile_type = $tile_types[$action];
    $total_cost = $tile_type['cost'] * $selected_cell_count;

    // コストとランクのチェック
    if ($_SESSION['money'] < $total_cost) {
      $_SESSION['error'] = "お金が足りません。（{$total_cost}G必要）";
    } elseif ($_SESSION['rank'] < $tile_type['rank']) {
      $_SESSION['error'] = "ランクが足りません。（ランク{$tile_type['rank']}が必要）";
    } else {
      $can_proceed = true;
      
      // 作物と収穫のチェック
      if ($action === 'harvest') {
        foreach ($cells as $row => $cols_data) {
          foreach (array_keys($cols_data) as $col) {
            $target_cell = $_SESSION['farm'][$row][$col];
            if (!$target_cell['crop']) {
              $_SESSION['error'] = "収穫できる作物がありません。";
              $can_proceed = false; break 2;
            }
            $crop_info = $tile_types[$target_cell['crop']];
            if (($_SESSION['turns'] - $target_cell['planted_at']) < $crop_info['growth_turns']) {
              $_SESSION['error'] = "作物はまだ成長途中です。";
              $can_proceed = false; break 2;
            }
          }
        }
      } elseif ($tile_type['type'] === 'crop') {
        foreach ($cells as $row => $cols_data) {
          foreach (array_keys($cols_data) as $col) {
            $target_cell = $_SESSION['farm'][$row][$col];
            if ($target_cell['type'] !== 'field' || $target_cell['crop'] !== null) {
              $_SESSION['error'] = "作物は空の畑にのみ植えられます。";
              $can_proceed = false; break 2;
            }
          }
        }
      }

      // 確認後、操作を実行
      if ($can_proceed) {
        $_SESSION['money'] -= $total_cost;
        $_SESSION['turns']++;
        
        foreach ($cells as $row => $cols_data) {
          foreach (array_keys($cols_data) as $col) {
            if ($action === 'harvest') {
              $crop_to_harvest = $_SESSION['farm'][$row][$col]['crop'];
              if (!isset($_SESSION['inventory'][$crop_to_harvest])) {
                $_SESSION['inventory'][$crop_to_harvest] = 0;
              }
              $_SESSION['inventory'][$crop_to_harvest]++;
              $_SESSION['farm'][$row][$col] = ['type'=>'field', 'crop'=>null, 'planted_at'=>null];
            } elseif ($tile_type['type'] === 'crop') {
              $_SESSION['farm'][$row][$col]['crop'] = $action;
              $_SESSION['farm'][$row][$col]['planted_at'] = $_SESSION['turns'];
            } else {
              $_SESSION['farm'][$row][$col] = ['type'=>$action, 'crop'=>null, 'planted_at'=>null];
            }
          }
        }
      }
    }
  }

  // URLフラグメントの生成
  $anchor = '';
  if ($max_row !== null && $max_col !== null) {
    $anchor = '#cells-' . $max_row . '-' . $max_col;
  }
  header("Location: " . $_SERVER['PHP_SELF'] . $anchor);
  exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>農場ゲーム</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <form action="index.php" method="post" class="farm">
    <table>
      <thead>
        <tr>
          <!-- 列番号 -->
          <th></th>
          <?php for ($i = 0; $i < $cols; $i++): ?>
            <th><?php echo $i + 1; ?></th>
          <?php endfor; ?>
        </tr>
      </thead>
      <tbody>
        <?php for ($i = 0; $i < $rows; $i++): ?>
          <tr>
            <td>
              <!-- 行番号 -->
              <?php echo $i + 1; ?>
            </td>
            <!-- 作物の成長度を表示 -->
            <?php for ($j = 0; $j < $cols; $j++):
              $cell = $_SESSION['farm'][$i][$j];
              $cell_bg_class = htmlspecialchars($cell['type']);
              $cell_symbol = '';

              if ($cell['crop']) {
                $crop_key = $cell['crop'];
                $cell_bg_class = $cell['crop'];
                $crop_info = $tile_types[$crop_key];
                if (($_SESSION['turns'] - $cell['planted_at']) >= $crop_info['growth_turns']) {
                  $cell_symbol = strtoupper(substr($crop_key, 0, 1));
                } else {
                  $cell_symbol = strtolower(substr($crop_key, 0, 1));
                }
              }
            ?>
              <td id="cells-<?php echo $i; ?>-<?php echo $j; ?>">
                <input type="checkbox" id="cells[<?php echo $i; ?>][<?php echo $j; ?>]" name="cells[<?php echo $i; ?>][<?php echo $j; ?>]">
                <label
                  for="cells[<?php echo $i; ?>][<?php echo $j; ?>]"
                  class="cell bg-<?php echo $cell_bg_class; ?>"
                  style="background-image: url('./assets/texture/<?php echo $cell_bg_class; ?>.png');"
                ><?php echo htmlspecialchars($cell_symbol); ?></label>
              </td>
            <?php endfor; ?>
          </tr>
        <?php endfor; ?>
      </tbody>
    </table>

    <details class="control float-box" open>
      <summary class="control float-box">≡</summary>
      <div>操作モード</div>
      <?php foreach ($tile_types as $type => $details): ?>
        <input type="radio" id="<?php echo $type; ?>" name="action" value="<?php echo $type; ?>" <?php if ($_SESSION['last_action'] === $type) echo 'checked'; ?>>
        <label for="<?php echo $type; ?>" class="bg-<?php echo $type; ?>"><?php echo htmlspecialchars($details['name']); ?> (<?php echo $details['cost']; ?>G)</label><br>
      <?php endforeach; ?>
      <input type="radio" id="wait" name="action" value="wait" <?php if ($_SESSION['last_action'] === 'wait') echo 'checked'; ?>>
      <label for="wait">待つ</label><br>
      <input type="submit" value="決定">
    </details>

    <details class="menu float-box">
      <summary class="menu float-box">≡</summary>
      <div>メニュー</div>
      <a href="shop.php">お店に行く</a>
      <hr />
      <div>所持品</div>
      <?php if (empty($_SESSION['inventory'])): ?>
        <p>空です</p>
      <?php else: ?>
        <ul>
          <?php foreach ($_SESSION['inventory'] as $item => $quantity): ?>
            <li><?php echo htmlspecialchars($tile_types[$item]['name']); ?>: <?php echo $quantity; ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </details>
    <div class="stats float-box">
      お金: <?php echo $_SESSION['money']; ?>G / ランク: <?php echo $_SESSION['rank']; ?> / 回数: <?php echo $_SESSION['turns']; ?>回
    </div>
    <?php if ($error_message): ?>
      <div class="error-message" style="color: red;"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>
  </form>
  <style>
    <?php foreach ($tile_types as $type => $details): ?>
    .bg-<?php echo $type; ?> {
      background-color: <?php echo $details['color']; ?>;
    }
    <?php endforeach; ?>
  </style>
</body>
</html>
