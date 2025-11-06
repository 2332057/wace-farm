<?php
session_start();
require_once 'data.php';

// ゲームが開始されていない場合、index.phpにリダイレクト
if (!isset($_SESSION['money']) || !isset($_SESSION['rank'])) {
  header('Location: index.php');
  exit;
}

// セッションの初期化
$error_message = '';
if (isset($_SESSION['error'])) {
  $error_message = $_SESSION['error'];
  unset($_SESSION['error']);
}
$message = '';
if (isset($_SESSION['message'])) {
  $message = $_SESSION['message'];
  unset($_SESSION['message']);
}

// 操作の処理
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $action = $_POST['action'] ?? '';
  
  if (strpos($action, 'buy_') === 0) {
    $item_key = substr($action, 4);
    $quantity = (int)($_POST['buy_quantity'][$item_key] ?? 1);
    
    if (isset($tile_types[$item_key]) && $quantity > 0) {
      $item = $tile_types[$item_key];
      $total_cost = $item['cost'] * $quantity;
      
      if ($_SESSION['money'] >= $total_cost) {
        $_SESSION['money'] -= $total_cost;
        if (!isset($_SESSION['inventory'][$item_key])) {
          $_SESSION['inventory'][$item_key] = 0;
        }
        $_SESSION['inventory'][$item_key] += $quantity;
        $_SESSION['message'] = htmlspecialchars($item['name']) . "を" . $quantity . "個買いました。";
      } else {
        $_SESSION['error'] = "お金が足りません。";
      }
    }
  } elseif (strpos($action, 'sell_') === 0) {
    $item_key = substr($action, 5);
    $quantity = (int)($_POST['sell_quantity'][$item_key] ?? 1);

    if (isset($_SESSION['inventory'][$item_key]) && $_SESSION['inventory'][$item_key] >= $quantity && $quantity > 0) {
      $item = $tile_types[$item_key];
      if (isset($item['sell_price'])) {
        $total_earnings = $item['sell_price'] * $quantity;
        $_SESSION['money'] += $total_earnings;
        $_SESSION['inventory'][$item_key] -= $quantity;
        if ($_SESSION['inventory'][$item_key] == 0) {
          unset($_SESSION['inventory'][$item_key]);
        }
        $_SESSION['message'] = htmlspecialchars($item['name']) . "を" . $quantity . "個売りました。 (" . $total_earnings . "G)";
      } else {
        $_SESSION['error'] = "このアイテムは売れません。";
      }
    } else {
      $_SESSION['error'] = "売るためのアイテムが足りません。";
    }
  } elseif ($action === 'rankup') {
    // 500Gで1ランクアップ
    $cost = 500;
    if ($_SESSION['money'] >= $cost) {
      $_SESSION['money'] -= $cost;
      $_SESSION['rank'] += 1;
      $_SESSION['message'] = "ランクが1上がりました。現在のランク: " . intval($_SESSION['rank']) . "。";
    } else {
      $_SESSION['error'] = "お金が足りません。ランクアップには{$cost}G必要です。";
    }
  }

  header("Location: " . $_SERVER['PHP_SELF']);
  exit;
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>お店 - 農場ゲーム</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <h1>お店</h1>
  <p><a href="index.php">農場に戻る</a></p>

  <?php if ($error_message): ?>
    <p style="color: red;"><?php echo htmlspecialchars($error_message); ?></p>
  <?php endif; ?>
  <?php if ($message): ?>
    <p style="color: green;"><?php echo htmlspecialchars($message); ?></p>
  <?php endif; ?>

  <div class="stats">
    <p>所持金: <?php echo $_SESSION['money']; ?>G</p>
  </div>

  <div class="rankup-section">
    <h2>ランク</h2>
    <p>現在のランク: <?php echo intval($_SESSION['rank']); ?></p>
    <form action="shop.php" method="post">
      <input type="hidden" name="action" value="rankup">
      <button type="submit">500Gでランクを1上げる</button>
    </form>
  </div>

  <div class="shop-container">
    <div class="buy-section">
      <h2>買う</h2>
      <form action="shop.php" method="post">
        <table>
          <thead>
            <tr>
              <th>商品</th>
              <th>価格</th>
              <th>数量</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($tile_types as $key => $details): ?>
              <?php if ($details['type'] === 'crop'): ?>
              <tr>
                <td><?php echo htmlspecialchars($details['name']); ?></td>
                <td><?php echo htmlspecialchars($details['cost']); ?>G</td>
                <td><input type="number" name="buy_quantity[<?php echo $key; ?>]" value="1" min="1"></td>
                <td><button type="submit" name="action" value="buy_<?php echo $key; ?>">買う</button></td>
              </tr>
              <?php endif; ?>
            <?php endforeach; ?>
          </tbody>
        </table>
      </form>
    </div>

    <div class="sell-section">
      <h2>売る</h2>
      <?php if (empty($_SESSION['inventory'])): ?>
        <p>売れるものがありません</p>
      <?php else: ?>
      <form action="shop.php" method="post">
        <table>
          <thead>
            <tr>
              <th>商品</th>
              <th>在庫</th>
              <th>売値</th>
              <th>数量</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($_SESSION['inventory'] as $item => $quantity): ?>
              <?php if ($quantity > 0): ?>
              <tr>
                <td><?php echo htmlspecialchars($tile_types[$item]['name']); ?></td>
                <td><?php echo $quantity; ?></td>
                <td><?php echo htmlspecialchars($tile_types[$item]['sell_price'] ?? 'N/A'); ?>G</td>
                <td><input type="number" name="sell_quantity[<?php echo $item; ?>]" value="1" min="1" max="<?php echo $quantity; ?>"></td>
                <td><button type="submit" name="action" value="sell_<?php echo $item; ?>">売る</button></td>
              </tr>
              <?php endif; ?>
            <?php endforeach; ?>
          </tbody>
        </table>
      </form>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
