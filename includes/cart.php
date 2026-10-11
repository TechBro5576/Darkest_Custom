<?php
/**
 * cart.php
 * Cart + Checkout — single page.
 *
 * Session cart structure:
 *   $_SESSION['cart'] = [
 *     '<key>' => [
 *       'product_id'  => int,
 *       'name'        => string,
 *       'price'       => float,
 *       'qty'         => int,
 *       'size'        => string|null,
 *       'color'       => string|null,
 *       'design_file' => string|null,  // filename in /uploads/designs
 *       'preview'     => string|null,  // filename in /uploads/previews
 *       'notes'       => string,
 *     ],
 *     ...
 *   ]
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// ------------------------------------------------------------
//  Initialize cart
// ------------------------------------------------------------
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// ------------------------------------------------------------
//  Handle cart mutations (POST)
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    // ---- Remove item ----
    if ($action === 'remove') {
        $key = (string)($_POST['key'] ?? '');
        if ($key !== '' && isset($_SESSION['cart'][$key])) {
            // Best-effort: delete design files for this line
            $line = $_SESSION['cart'][$key];
            foreach (['design_file' => DESIGN_PATH, 'preview' => PREVIEW_PATH] as $f => $dir) {
                if (!empty($line[$f])) {
                    $path = $dir . '/' . basename($line[$f]);
                    if (is_file($path)) @unlink($path);
                }
            }
            unset($_SESSION['cart'][$key]);
        }
        redirect('/cart.php');
    }

    // ---- Update quantity ----
    if ($action === 'update') {
        $key = (string)($_POST['key'] ?? '');
        $qty = max(1, min(99, (int)($_POST['qty'] ?? 1)));
        if (isset($_SESSION['cart'][$key])) {
            $_SESSION['cart'][$key]['qty'] = $qty;
        }
        redirect('/cart.php');
    }

    // ---- Clear cart ----
    if ($action === 'clear') {
        foreach ($_SESSION['cart'] as $line) {
            foreach (['design_file' => DESIGN_PATH, 'preview' => PREVIEW_PATH] as $f => $dir) {
                if (!empty($line[$f])) {
                    $path = $dir . '/' . basename($line[$f]);
                    if (is_file($path)) @unlink($path);
                }
            }
        }
        $_SESSION['cart'] = [];
        redirect('/cart.php');
    }

    // ---- Place order ----
    if ($action === 'checkout') {
        // Rate limit checkout attempts
        if (!rate_limit('checkout', 10, 600)) {
            flash('error', 'Too many attempts. Please wait a few minutes.');
            redirect('/cart.php');
        }

        $errors = [];

        // Collect + validate fields
        $name    = trim((string)($_POST['customer_name']    ?? ''));
        $phone   = sanitize_phone((string)($_POST['customer_phone'] ?? ''));
        $email   = trim((string)($_POST['customer_email']   ?? ''));
        $address = trim((string)($_POST['delivery_address'] ?? ''));
        $state   = trim((string)($_POST['delivery_state']   ?? ''));
        $notes   = trim((string)($_POST['customer_notes']   ?? ''));

        if (strlen($name) < 2 || strlen($name) > 100) {
            $errors[] = 'Please enter your full name.';
        }
        if ($phone === '') {
            $errors[] = 'Please enter a valid Nigerian phone number.';
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email address looks invalid.';
        }
        if (strlen($address) < 5) {
            $errors[] = 'Please enter a delivery address.';
        }
        if ($state === '') {
            $errors[] = 'Please select a state.';
        }
        if (empty($_SESSION['cart'])) {
            $errors[] = 'Your cart is empty.';
        }

        if ($errors) {
            foreach ($errors as $e) flash('error', $e);
            // Fall through to render the page with errors + repopulated form
        } else {
            // --------------------------------------------------------
            //  Compute totals from DB (never trust client prices)
            // --------------------------------------------------------
            $subtotal = 0.0;
            $items    = [];

            try {
                $stmt = $pdo->prepare(
                    'SELECT id, name, base_price, is_active FROM products WHERE id = ? LIMIT 1'
                );
                foreach ($_SESSION['cart'] as $key => $line) {
                    $stmt->execute([(int)$line['product_id']]);
                    $p = $stmt->fetch();

                    if (!$p || !$p['is_active']) {
                        throw new RuntimeException('One of your items is no longer available.');
                    }

                    $qty       = max(1, min(99, (int)$line['qty']));
                    $unitPrice = (float)$p['base_price'];
                    $lineTotal = $unitPrice * $qty;
                    $subtotal += $lineTotal;

                    $items[] = [
                        'product_id'    => (int)$p['id'],
                        'product_name'  => $p['name'],
                        'unit_price'    => $unitPrice,
                        'quantity'      => $qty,
                        'size'          => $line['size']  ?? null,
                        'color'         => $line['color'] ?? null,
                        'design_file'   => $line['design_file'] ?? null,
                        'preview_image' => $line['preview']     ?? null,
                        'notes'         => $line['notes']       ?? null,
                    ];
                }
            } catch (Throwable $ex) {
                flash('error', $ex->getMessage());
                redirect('/cart.php');
            }

            // Delivery fee — flat default for v1
            $deliveryFee = (float)get_setting('delivery_fee_default', '2500');
            $total       = $subtotal + $deliveryFee;

            // --------------------------------------------------------
            //  Create order (pending payment)
            // --------------------------------------------------------
            try {
                $pdo->beginTransaction();

                $orderRef = generate_order_ref();

                $ins = $pdo->prepare(
                    'INSERT INTO orders
                     (order_ref, customer_name, customer_phone, customer_email,
                      delivery_address, delivery_state,
                      subtotal, delivery_fee, total,
                      payment_status, fulfillment_status, customer_notes)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $ins->execute([
                    $orderRef,
                    $name, $phone, $email !== '' ? $email : null,
                    $address, $state,
                    $subtotal, $deliveryFee, $total,
                    'pending', 'new', $notes !== '' ? $notes : null,
                ]);

                $orderId = (int)$pdo->lastInsertId();

                $insItem = $pdo->prepare(
                    'INSERT INTO order_items
                     (order_id, product_id, product_name, unit_price, quantity,
                      selected_size, selected_color,
                      design_file, preview_image, customization_notes)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                foreach ($items as $it) {
                    $insItem->execute([
                        $orderId, $it['product_id'], $it['product_name'],
                        $it['unit_price'], $it['quantity'],
                        $it['size'], $it['color'],
                        $it['design_file'], $it['preview_image'],
                        $it['notes'],
                    ]);
                }

                $pdo->commit();
            } catch (Throwable $ex) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                error_log('[CHECKOUT] ' . $ex->getMessage());
                flash('error', 'Could not create your order. Please try again.');
                redirect('/cart.php');
            }

            // --------------------------------------------------------
            //  Init Paystack
            // --------------------------------------------------------
            $secretKey = PAYSTACK_SECRET_KEY ?: get_setting('paystack_secret_key', '');
            if ($secretKey === '') {
                flash('error', 'Payment is not configured yet. Please contact us on WhatsApp.');
                redirect('/cart.php');
            }

            $payload = [
                'email'        => $email !== '' ? $email : 'orders@darkestcustom.com',
                'amount'       => (int)round($total * 100),   // kobo
                'reference'    => $orderRef,
                'currency'     => 'NGN',
                'callback_url' => APP_URL . '/order-success.php?ref=' . urlencode($orderRef),
                'metadata'     => [
                    'order_id'      => $orderId,
                    'customer_name' => $name,
                    'phone'         => $phone,
                ],
            ];

            $ch = curl_init('https://api.paystack.co/transaction/initialize');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($payload),
                CURLOPT_HTTPHEADER     => [
                    'Authorization: Bearer ' . $secretKey,
                    'Content-Type: application/json',
                ],
                CURLOPT_TIMEOUT        => 30,
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $result = json_decode((string)$response, true);

            if ($httpCode !== 200 || empty($result['status']) || empty($result['data']['authorization_url'])) {
                error_log('[PAYSTACK INIT] ' . $response);
                flash('error', 'Could not reach payment provider. Please try again or chat to order.');
                redirect('/cart.php');
            }

            // Persist the paystack reference so we can match the webhook later
            try {
                $pdo->prepare('UPDATE orders SET payment_ref = ? WHERE id = ?')
                    ->execute([$result['data']['reference'], $orderId]);
            } catch (PDOException $e) {
                error_log('[PAYSTACK REF SAVE] ' . $e->getMessage());
            }

            // Clear cart now — order is created and will be marked paid by webhook
            $_SESSION['cart'] = [];

            // Send the customer to Paystack
            redirect($result['data']['authorization_url']);
        }
    }
}

// ------------------------------------------------------------
//  Prepare display data
// ------------------------------------------------------------
$cart     = $_SESSION['cart'];
$flashes  = take_flashes();

// Recompute subtotal from live DB prices (best-effort; cart page is display only)
$subtotal = 0.0;
$display  = [];
if ($cart) {
    try {
        $stmt = $pdo->prepare('SELECT id, name, base_price, image, is_active FROM products WHERE id = ? LIMIT 1');
        foreach ($cart as $key => $line) {
            $stmt->execute([(int)$line['product_id']]);
            $p = $stmt->fetch();

            if (!$p) continue;

            $qty       = max(1, (int)$line['qty']);
            $unitPrice = (float)$p['base_price'];
            $lineTotal = $unitPrice * $qty;
            $subtotal += $lineTotal;

            $display[$key] = [
                'name'       => $p['name'],
                'image'      => $p['image'],
                'unit_price' => $unitPrice,
                'qty'        => $qty,
                'line_total' => $lineTotal,
                'size'       => $line['size']  ?? null,
                'color'      => $line['color'] ?? null,
                'preview'    => $line['preview'] ?? null,
                'available'  => (bool)$p['is_active'],
            ];
        }
    } catch (PDOException $e) {
        error_log('[CART DISPLAY] ' . $e->getMessage());
    }
}

$deliveryFee = (float)get_setting('delivery_fee_default', '2500');
$total       = $subtotal + ($display ? $deliveryFee : 0);
$siteName    = get_setting('site_name', 'Darkest Custom');
$whatsapp    = get_setting('whatsapp_number', '2347035770759');

// Nigerian states for the dropdown
$states = [
    'Abia','Adamawa','Akwa Ibom','Anambra','Bauchi','Bayelsa','Benue','Borno',
    'Cross River','Delta','Ebonyi','Edo','Ekiti','Enugu','FCT - Abuja','Gombe',
    'Imo','Jigawa','Kaduna','Kano','Katsina','Kebbi','Kogi','Kwara','Lagos',
    'Nasarawa','Niger','Ogun','Ondo','Osun','Oyo','Plateau','Rivers','Sokoto',
    'Taraba','Yobe','Zamfara',
];

// Preserve form values on error
$form = [
    'customer_name'    => $_POST['customer_name']    ?? '',
    'customer_phone'   => $_POST['customer_phone']   ?? '',
    'customer_email'   => $_POST['customer_email']   ?? '',
    'delivery_address' => $_POST['delivery_address'] ?? '',
    'delivery_state'   => $_POST['delivery_state']   ?? '',
    'customer_notes'   => $_POST['customer_notes']   ?? '',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Your Cart — <?= e($siteName) ?></title>
<style>
  * { box-sizing: border-box; }
  body {
    margin: 0;
    font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
    background: #F7F9FC; color: #374151;
    padding-bottom: 120px;
  }
  .wrap { max-width: 720px; margin: 0 auto; padding: 16px; }

  header {
    background: #fff; border-bottom: 1px solid #EEF2F7;
    padding: 14px 16px; display: flex; align-items: center; gap: 12px;
    position: sticky; top: 0; z-index: 10;
  }
  header h1 {
    font-family: 'Playfair Display', Georgia, serif;
    color: #0B1E3F; margin: 0; font-size: 20px;
  }
  header a { color: #0B1E3F; text-decoration: none; font-weight: 500; font-size: 14px; }

  .flash {
    padding: 12px 16px; border-radius: 10px; margin-bottom: 12px;
    font-size: 14px; border: 1px solid;
  }
  .flash.error   { background: #FEF2F2; border-color: #FECACA; color: #B91C1C; }
  .flash.success { background: #F0FDF4; border-color: #BBF7D0; color: #166534; }
  .flash.info    { background: #EAF4FB; border-color: #A8D1F0; color: #12294F; }

  .card {
    background: #fff; border-radius: 16px;
    box-shadow: 0 1px 2px rgba(11,30,63,.06);
    padding: 20px; margin-bottom: 16px;
  }

  .line {
    display: grid; grid-template-columns: 64px 1fr auto; gap: 12px;
    padding: 12px 0; border-bottom: 1px solid #EEF2F7;
    align-items: center;
  }
  .line:last-child { border-bottom: none; }
  .line img {
    width: 64px; height: 64px; object-fit: cover;
    border-radius: 10px; background: #EEF2F7;
  }
  .line .meta { font-size: 13px; color: #6B7280; margin-top: 2px; }
  .line .price { font-weight: 600; color: #0B1E3F; white-space: nowrap; }
  .line .controls { display: flex; align-items: center; gap: 8px; margin-top: 6px; }
  .line .controls input[type=number] {
    width: 56px; padding: 6px; border: 1.5px solid #E5E7EB;
    border-radius: 8px; font-size: 14px; text-align: center;
  }
  .line button.link {
    background: none; border: none; color: #B91C1C;
    font-size: 13px; cursor: pointer; padding: 0;
    text-decoration: underline;
  }
  .preview-badge {
    display: inline-block; font-size: 11px; font-weight: 600;
    background: #EAF4FB; color: #12294F; padding: 2px 8px;
    border-radius: 999px; margin-left: 6px;
  }
  .unavailable { color: #B91C1C; font-size: 12px; }

  .empty { text-align: center; padding: 40px 20px; color: #6B7280; }
  .empty a {
    display: inline-block; margin-top: 16px; padding: 12px 24px;
    background: #4A9FE0; color: #fff; text-decoration: none;
    border-radius: 10px; font-weight: 600;
  }

  h2 {
    font-family: 'Playfair Display', Georgia, serif;
    color: #0B1E3F; font-size: 20px; margin: 0 0 12px;
  }
  .totals { font-size: 15px; }
  .totals .row { display: flex; justify-content: space-between; padding: 6px 0; }
  .totals .row.total {
    border-top: 1px solid #EEF2F7; margin-top: 8px; padding-top: 12px;
    font-size: 18px; font-weight: 700; color: #0B1E3F;
  }

  label {
    display: block; font-size: 13px; font-weight: 600;
    color: #0B1E3F; margin: 14px 0 6px;
  }
  input[type=text], input[type=email], input[type=tel],
  textarea, select {
    width: 100%; min-height: 48px; padding: 0 14px;
    border: 1.5px solid #E5E7EB; border-radius: 10px;
    font-size: 15px; font-family: inherit; color: #0A0A0A;
    background: #fff;
  }
  textarea { padding: 12px 14px; min-height: 80px; resize: vertical; }
  input:focus, textarea:focus, select:focus {
    outline: none; border-color: #4A9FE0;
  }

  .pay-bar {
    position: fixed; bottom: 0; left: 0; right: 0;
    background: #fff; border-top: 1px solid #EEF2F7;
    padding: 12px 16px calc(12px + env(safe-area-inset-bottom));
    box-shadow: 0 -4px 12px rgba(11,30,63,.06);
    z-index: 20;
  }
  .pay-bar-inner {
    max-width: 720px; margin: 0 auto;
    display: flex; align-items: center; gap: 12px;
  }
  .pay-bar .amt {
    font-size: 18px; font-weight: 700; color: #0B1E3F; flex: 1;
  }
  .pay-bar button {
    min-height: 52px; padding: 0 24px;
    background: #4A9FE0; color: #fff; border: none; border-radius: 10px;
    font-size: 16px; font-weight: 600; cursor: pointer;
    transition: background 150ms;
  }
  .pay-bar button:hover { background: #12294F; }
  .pay-bar button:disabled {
    background: #9CA3AF; cursor: not-allowed;
  }
  .trust {
    text-align: center; font-size: 12px; color: #9CA3AF;
    margin-top: 6px;
  }
</style>
</head>
<body>

<header>
  <a href="/shop.php">←</a>
  <h1>Your Cart</h1>
</header>

<div class="wrap">

  <?php foreach ($flashes as $f): ?>
    <div class="flash <?= e($f['type']) ?>"><?= e($f['message']) ?></div>
  <?php endforeach; ?>

  <?php if (empty($display)): ?>

    <div class="card empty">
      <p>Your cart is empty.</p>
      <a href="/shop.php">Browse products</a>
    </div>

  <?php else: ?>

    <!-- CART LINES -->
    <div class="card">
      <?php foreach ($display as $key => $line): ?>
        <div class="line">
          <img src="<?= e($line['image'] ? '/assets/img/products/' . $line['image'] : '/assets/img/placeholder.png') ?>"
               alt="<?= e($line['name']) ?>">
          <div>
            <div style="font-weight:600; color:#0B1E3F;">
              <?= e($line['name']) ?>
              <?php if ($line['preview']): ?>
                <span class="preview-badge">Customized</span>
              <?php endif; ?>
            </div>
            <div class="meta">
              <?php if ($line['size']): ?>Size: <?= e($line['size']) ?><?php endif; ?>
              <?php if ($line['size'] && $line['color']): ?> · <?php endif; ?>
              <?php if ($line['color']): ?>Color: <?= e($line['color']) ?><?php endif; ?>
            </div>
            <?php if (!$line['available']): ?>
              <div class="unavailable">No longer available</div>
            <?php endif; ?>
            <div class="controls">
              <form method="post" style="display:flex; align-items:center; gap:6px;">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="key" value="<?= e($key) ?>">
                <input type="number" name="qty" value="<?= (int)$line['qty'] ?>"
                       min="1" max="99" onchange="this.form.submit()">
              </form>
              <form method="post" onsubmit="return confirm('Remove this item?')">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="remove">
                <input type="hidden" name="key" value="<?= e($key) ?>">
                <button type="submit" class="link">Remove</button>
              </form>
            </div>
          </div>
          <div class="price"><?= e(money($line['line_total'])) ?></div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- CHECKOUT FORM -->
    <form method="post" id="checkout-form">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="checkout">

      <div class="card">
        <h2>Delivery details</h2>

        <label for="customer_name">Full name</label>
        <input type="text" id="customer_name" name="customer_name" required
               maxlength="100" value="<?= e($form['customer_name']) ?>">

        <label for="customer_phone">WhatsApp number</label>
        <input type="tel" id="customer_phone" name="customer_phone" required
               placeholder="070 3577 0759" value="<?= e($form['customer_phone']) ?>">

        <label for="customer_email">Email (for receipt)</label>
        <input type="email" id="customer_email" name="customer_email"
               placeholder="optional" value="<?= e($form['customer_email']) ?>">

        <label for="delivery_address">Delivery address</label>
        <textarea id="delivery_address" name="delivery_address" required
                  maxlength="500"><?= e($form['delivery_address']) ?></textarea>

        <label for="delivery_state">State</label>
        <select id="delivery_state" name="delivery_state" required>
          <option value="">Select state…</option>
          <?php foreach ($states as $s): ?>
            <option value="<?= e($s) ?>"<?= $form['delivery_state'] === $s ? ' selected' : '' ?>>
              <?= e($s) ?>
            </option>
          <?php endforeach; ?>
        </select>

        <label for="customer_notes">Notes (optional)</label>
        <textarea id="customer_notes" name="customer_notes" maxlength="500"
                  placeholder="Anything we should know about your order?"><?= e($form['customer_notes']) ?></textarea>
      </div>

      <div class="card totals">
        <div class="row"><span>Subtotal</span><span><?= e(money($subtotal)) ?></span></div>
        <div class="row"><span>Delivery</span><span><?= e(money($deliveryFee)) ?></span></div>
        <div class="row total"><span>Total</span><span><?= e(money($total)) ?></span></div>
      </div>
    </form>

  <?php endif; ?>

</div>

<?php if (!empty($display)): ?>
<div class="pay-bar">
  <div class="pay-bar-inner">
    <div class="amt"><?= e(money($total)) ?></div>
    <button type="submit" form="checkout-form" id="pay-btn">
      Pay <?= e(money($total)) ?> →
    </button>
  </div>
  <div class="trust">🔒 Secured by Paystack</div>
</div>
<?php endif; ?>

<script>
document.getElementById('checkout-form')?.addEventListener('submit', function () {
  const btn = document.getElementById('pay-btn');
  if (!btn) return;
  btn.disabled = true;
  btn.textContent = 'Redirecting to payment…';
});
</script>

</body>
</html> 
