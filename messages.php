<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit;
}

$me = (int)$_SESSION['user_id'];

/* DB connection */
$host = 'localhost';
$db   = 'realestate';
$user = 'root';
$pass = 'root';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Database connection failed.");
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/* Get property_id */
$propertyId = isset($_GET['property_id']) ? (int)$_GET['property_id'] : 0;
if ($propertyId <= 0) {
    die("Missing property_id.");
}

/* Get property seller_id + title */
$stmt = $pdo->prepare("SELECT seller_id, title FROM properties WHERE property_id = :pid LIMIT 1");
$stmt->execute([':pid' => $propertyId]);
$prop = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$prop) die("Property not found.");

$sellerId = (int)$prop['seller_id'];
$propertyTitle = (string)$prop['title'];

/*
  Decide who the other user is:

  - If I'm the buyer: other user is the seller
  - If I'm the seller: other user must be a buyer (seller can have multiple chats),
    so we use buyer_id from the URL. If missing, we show inbox list.
*/
$buyerId = isset($_GET['buyer_id']) ? (int)$_GET['buyer_id'] : 0;
$otherUser = 0;

if ($me === $sellerId) {
    // I'm the seller
    if ($buyerId > 0) {
        $otherUser = $buyerId;
    }
} else {
    // I'm a buyer
    $otherUser = $sellerId;
}

/* Send message */
$errorMsg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $text = trim($_POST['message'] ?? '');

    if ($text === '') {
        $errorMsg = "Message cannot be empty.";
    } elseif ($otherUser <= 0) {
        $errorMsg = "Please select a buyer conversation.";
    } else {
        $stmtSend = $pdo->prepare("
            INSERT INTO messages (senderID, receiverID, property_id, messages)
            VALUES (:sid, :rid, :pid, :msg)
        ");
        $stmtSend->execute([
            ':sid' => $me,
            ':rid' => $otherUser,
            ':pid' => $propertyId,
            ':msg' => $text
        ]);

        // redirect to avoid resubmission
        if ($me === $sellerId) {
            header("Location: messages.php?property_id=".$propertyId."&buyer_id=".$otherUser);
        } else {
            header("Location: messages.php?property_id=".$propertyId);
        }
        exit;
    }
}

/* If I'm seller and no buyer selected -> show list of buyers who messaged about this property */
$buyerThreads = [];
if ($me === $sellerId && $otherUser <= 0) {
    $stmtThreads = $pdo->prepare("
        SELECT DISTINCT
            CASE WHEN senderID = :me THEN receiverID ELSE senderID END AS buyer_id
        FROM messages
        WHERE property_id = :pid
          AND (senderID = :me OR receiverID = :me)
        ORDER BY buyer_id DESC
    ");
    $stmtThreads->execute([':me' => $me, ':pid' => $propertyId]);
    $buyerThreads = $stmtThreads->fetchAll(PDO::FETCH_ASSOC);
}

/* Fetch other user name (if selected) */
$otherName = "";
if ($otherUser > 0) {
    $stmtName = $pdo->prepare("SELECT firstname, lastname FROM users WHERE user_id = :uid LIMIT 1");
    $stmtName->execute([':uid' => $otherUser]);
    $u = $stmtName->fetch(PDO::FETCH_ASSOC);
    if ($u) $otherName = trim($u['firstname'].' '.$u['lastname']);
    if ($otherName === '') $otherName = "User #".$otherUser;
}

/* Fetch messages for this chat */
$chat = [];
if ($otherUser > 0) {
    $stmtChat = $pdo->prepare("
        SELECT senderID, receiverID, messages, timestamp
        FROM messages
        WHERE property_id = :pid
          AND (
            (senderID = :me AND receiverID = :other)
            OR
            (senderID = :other AND receiverID = :me)
          )
        ORDER BY timestamp ASC
    ");
    $stmtChat->execute([':pid'=>$propertyId, ':me'=>$me, ':other'=>$otherUser]);
    $chat = $stmtChat->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Chat</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
  body{margin:0;font-family:Arial,sans-serif;background:#f2f4f7;color:#0b1320}
  .wrap{max-width:900px;margin:0 auto;height:100vh;display:flex;flex-direction:column}
  .top{background:#fff;border-bottom:1px solid #e6e9ef;padding:12px 14px;display:flex;align-items:center;justify-content:space-between}
  .top a{text-decoration:none;border:1px solid #cfd6dd;border-radius:10px;padding:8px 10px;background:#fff;color:#0b1320}
  .title{font-weight:800}
  .sub{color:#5f6b7a;font-size:13px;margin-top:2px}

  .chat-area{flex:1;overflow:auto;padding:14px;display:flex;flex-direction:column;gap:10px;background:#f4f6f8}
  .bubble{max-width:72%;padding:10px 12px;border-radius:16px;line-height:1.35}
  .mine{align-self:flex-end;background:#dbeafe}
  .theirs{align-self:flex-start;background:#fff;border:1px solid #e5e7eb}
  .time{font-size:11px;color:#6b7785;margin-top:6px}

  .composer{background:#fff;border-top:1px solid #e6e9ef;padding:10px;display:flex;gap:10px}
  .composer textarea{flex:1;resize:none;height:44px;padding:10px;border:1px solid #cfd6dd;border-radius:14px;font-size:15px}
  .composer button{border:none;background:#2196f3;color:#fff;border-radius:14px;padding:10px 16px;font-weight:800;cursor:pointer}

  .err{background:#ffebee;border:1px solid #ef9a9a;color:#b71c1c;padding:10px 12px;border-radius:10px;margin:10px}
  .picker{background:#fff;border:1px solid #e6e9ef;border-radius:14px;padding:12px;margin:14px}
  .picker a{display:block;padding:10px;border:1px solid #eef1f5;border-radius:12px;text-decoration:none;color:#0b1320;margin-top:10px}
  .picker a:hover{background:#f6f8fa}
</style>
</head>
<body>

<div class="wrap">
  <div class="top">
    <a href="listings.php">Back</a>
    <div>
      <div class="title">Messages</div>
      <div class="sub">About: <?php echo h($propertyTitle); ?></div>
    </div>
    <div style="width:64px;"></div>
  </div>

  <?php if ($errorMsg !== ''): ?>
    <div class="err"><?php echo h($errorMsg); ?></div>
  <?php endif; ?>

  <?php if ($me === $sellerId && $otherUser <= 0): ?>
    <!-- Seller must pick which buyer to chat with -->
    <div class="picker">
      <strong>Select a buyer conversation</strong>
      <div class="sub">Buyers who messaged about this property will appear here.</div>

      <?php if (empty($buyerThreads)): ?>
        <div class="sub" style="margin-top:10px;">No buyer messages yet.</div>
      <?php else: ?>
        <?php foreach ($buyerThreads as $b): ?>
          <?php $bid = (int)$b['buyer_id']; ?>
          <a href="messages.php?property_id=<?php echo $propertyId; ?>&buyer_id=<?php echo $bid; ?>">
            Chat with Buyer #<?php echo $bid; ?>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  <?php elseif ($otherUser > 0): ?>

    <div class="chat-area" id="chatArea">
      <?php if (empty($chat)): ?>
        <div class="sub">No messages yet. Send the first one below.</div>
      <?php else: ?>
        <?php foreach ($chat as $m): ?>
          <?php $isMe = ((int)$m['senderID'] === $me); ?>
          <div class="bubble <?php echo $isMe ? 'mine' : 'theirs'; ?>">
            <?php echo h($m['messages']); ?>
            <div class="time"><?php echo h(date('d/m/Y H:i', strtotime($m['timestamp']))); ?></div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <form class="composer" method="post">
      <textarea name="message" placeholder="Type a message..." required></textarea>
      <button type="submit">Send</button>
    </form>

    <script>
      // auto-scroll to bottom like a chat app
      const chat = document.getElementById('chatArea');
      if (chat) chat.scrollTop = chat.scrollHeight;
    </script>

  <?php else: ?>
    <div class="picker">
      <div class="sub">Open a chat from a listing by pressing “Message”.</div>
    </div>
  <?php endif; ?>

</div>
</body>
</html>