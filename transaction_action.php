<?php
require_once 'config.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS stock_movements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        `change` INT NOT NULL,
        `type` VARCHAR(20) NOT NULL,
        note VARCHAR(255) DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX (product_id),
        FOREIGN KEY (product_id) REFERENCES products(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (Exception $e) {}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    if ($action === 'products') {
        $stmt = $pdo->query("SELECT id, name, price, stock FROM products ORDER BY name");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }
    echo json_encode([]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);

    if (is_array($json) && isset($json['action'])) {
        $action = $json['action'];
        $cart = $json['cart'] ?? [];
        $total = (float)($json['total'] ?? 0);
    } else {
        $action = $_POST['action'] ?? '';
        $cart = json_decode($_POST['cart'] ?? '[]', true) ?: [];
        $total = (float)($_POST['total'] ?? 0);
    }

    if ($action === 'process') {
        if (empty($cart)) {
            echo json_encode(['success'=>false,'message'=>'Keranjang kosong']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            foreach ($cart as $item) {
                $pid = (int)($item['id'] ?? 0);
                $qty = (int)($item['quantity'] ?? 0);
                if ($pid <= 0 || $qty <= 0) {
                    throw new Exception('Item tidak valid');
                }
                $st = $pdo->prepare('SELECT stock, name FROM products WHERE id=?');
                $st->execute([$pid]);
                $prod = $st->fetch(PDO::FETCH_ASSOC);
                if (!$prod) throw new Exception('Produk tidak ditemukan');
                if ((int)$prod['stock'] < $qty) throw new Exception('Stok '.$prod['name'].' tidak mencukupi');
            }

            $stmt = $pdo->prepare('INSERT INTO transactions (user_id, total_amount, transaction_date) VALUES (1, ?, NOW())');
            $stmt->execute([$total]);
            $trxId = $pdo->lastInsertId();

            $ins = $pdo->prepare('INSERT INTO transaction_items (transaction_id, product_id, quantity, price_at_transaction) VALUES (?, ?, ?, ?)');
            $upd = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ?');
            $mov = $pdo->prepare("INSERT INTO stock_movements (product_id, `change`, `type`, note) VALUES (?, ?, 'sale', ?)");

            foreach ($cart as $it) {
                $pid = (int)$it['id'];
                $qty = (int)$it['quantity'];
                $price = (float)$it['price'];
                $ins->execute([$trxId, $pid, $qty, $price]);
                $upd->execute([$qty, $pid]);
                $mov->execute([$pid, -$qty, 'trx #'.$trxId]);
            }

            $pdo->commit();
            echo json_encode(['success'=>true,'transaction_id'=>$trxId]);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
        }
        exit;
    }

    echo json_encode(['success'=>false,'message'=>'Action tidak valid']);
    exit;
}

echo json_encode(['success'=>false,'message'=>'Method not allowed']);
?>