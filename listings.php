<?php
// listings.php — corrected to your schema (no address column, no config.php)

// -------- DB CONNECTION (XAMPP defaults) --------
$host = 'localhost';
$db   = 'realestate';
$user = 'root';
$pass = 'root';
try {
  $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
  ]);
} catch (PDOException $e) {
  die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
}

// -------- INPUTS (GET) --------
$location = trim($_GET['location'] ?? ''); // matches location/city/postcode
$keyword  = trim($_GET['q'] ?? '');        // title/description
$min      = $_GET['min_price'] ?? '';
$max      = $_GET['max_price'] ?? '';
$sort     = $_GET['sort'] ?? 'price_desc'; // price_desc | price_asc | recent
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 10;
$offset   = ($page - 1) * $perPage;

// -------- WHERE (match your columns exactly) --------
$where  = ["status = 'For sale'"];
$params = [];

if ($location !== '') {
  $where[] = "(city LIKE :loc OR postcode LIKE :loc OR location LIKE :loc)";
  $params[':loc'] = "%{$location}%";
}
if ($keyword !== '') {
  $where[] = "(title LIKE :kw OR description LIKE :kw)";
  $params[':kw'] = "%{$keyword}%";
}
if ($min !== '' && is_numeric($min)) { $where[] = "price >= :minp"; $params[':minp'] = (int)$min; }
if ($max !== '' && is_numeric($max)) { $where[] = "price <= :maxp"; $params[':maxp'] = (int)$max; }

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// -------- SORT --------
switch ($sort) {
  case 'price_asc':  $orderSql = "ORDER BY price ASC, date_listed DESC"; break;
  case 'recent':     $orderSql = "ORDER BY date_listed DESC"; break;
  default:           $orderSql = "ORDER BY price DESC, date_listed DESC"; // price_desc
}

// -------- COUNT --------
$sqlCount = "SELECT COUNT(*) FROM properties {$whereSql}";
$stmt = $pdo->prepare($sqlCount);
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

// -------- FETCH (NO address; uses location/city/postcode) --------
$sql = "
  SELECT
    p.property_id AS id,
    p.property_type_name,
    p.title,
    p.description,
    p.price,
    p.location,
    p.city,
    p.postcode,
    p.date_listed,
    p.status,
    p.bedrooms,
    p.bathrooms,
    p.area_sqft,
    p.garden_sqft,
    p.garage,
    (
      SELECT pi.image_url
      FROM property_images pi
      WHERE pi.property_id = p.property_id
      ORDER BY pi.image_id ASC
      LIMIT 1
    ) AS main_image
  FROM properties p
  {$whereSql}
  {$orderSql}
  LIMIT :limit OFFSET :offset
";
$stmt = $pdo->prepare($sql);
foreach ($params as $k=>$v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// -------- HELPERS --------
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function moneyx($n){ return is_numeric($n) ? '£' . number_format((float)$n) : h($n); }
$priceStops = [0,50000,100000,150000,200000,250000,300000,400000,500000,600000,750000,1000000,1500000,2000000];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Browse Listings</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    :root { --bg:#f2f4f7; --white:#fff; --ink:#0b1320; --muted:#5f6b7a; --brand:#0a63ff; --pill:#e9eef8; }
    *{box-sizing:border-box} body{margin:0;font-family:system-ui,Segoe UI,Roboto,Arial,sans-serif;background:var(--bg);color:var(--ink)}
    .wrap{max-width:1100px;margin:0 auto;padding:0 1rem}
    header{background:var(--white);border-bottom:1px solid #e6e9ef;position:sticky;top:0;z-index:10}
    .bar{display:grid;grid-template-columns:1.2fr .9fr .9fr .9fr auto;gap:.5rem;padding:.8rem 0}
    .bar input,.bar select,.bar button{padding:.7rem .8rem;border:1px solid #d7dbe3;border-radius:12px;font-size:.95rem;background:#fff}
    .bar button{background:var(--brand);color:#fff;border-color:var(--brand);cursor:pointer}
    .crumbs{font-size:.9rem;color:var(--muted);margin:.4rem 0 .8rem}
    .summary{display:flex;justify-content:space-between;align-items:center;margin:.4rem 0 1rem;color:var(--muted)}
    .panel{background:var(--white);border:1px solid #e6e9ef;border-radius:14px;padding:.7rem .9rem;margin-bottom:.8rem;display:flex;gap:1rem;align-items:center}
    .panel .left{display:flex;gap:.9rem;align-items:center}
    .panel .right{margin-left:auto}
    .results{display:grid;grid-template-columns:1fr;gap:.8rem;margin-bottom:1.2rem}
    .card{display:grid;grid-template-columns:320px 1fr;gap:1rem;background:var(--white);border:1px solid #e6e9ef;border-radius:14px;overflow:hidden}
    .thumb{background:#e8ebf3;aspect-ratio:4/3}
    .thumb img{width:100%;height:100%;object-fit:cover;display:block}
    .content{padding:.9rem}
    .title{font-weight:700;margin:.1rem 0 .4rem}
    .line{color:var(--muted);font-size:.95rem;margin-bottom:.5rem}
    .pill{display:inline-block;background:var(--pill);padding:.25rem .5rem;border-radius:999px;font-size:.85rem;margin-right:.35rem}
    .price{font-size:1.25rem;font-weight:800;margin:.5rem 0}
    .meta{display:flex;gap:1rem;color:var(--muted);font-size:.95rem;margin-bottom:.4rem}
    .agent{display:flex;justify-content:space-between;align-items:center;border-top:1px solid #eef1f6;padding-top:.7rem;color:var(--muted);font-size:.93rem}
    .agent .cta a{margin-left:.6rem;text-decoration:none;padding:.45rem .7rem;border:1px solid #d7dbe3;border-radius:10px;color:var(--ink);background:#fff}
    .pager{display:flex;justify-content:center;gap:.4rem;margin:1.2rem 0 2rem}
    .pager a,.pager span{padding:.5rem .8rem;border:1px solid #d7dbe3;border-radius:12px;background:#fff;text-decoration:none;color:var(--ink)}
    .current{background:var(--ink);color:#fff;border-color:var(--ink)}
    @media(max-width:980px){.bar{grid-template-columns:1fr 1fr 1fr 1fr auto}.card{grid-template-columns:1fr}}
    @media(max-width:640px){.bar{grid-template-columns:1fr 1fr;grid-auto-rows:auto}.bar .wide{grid-column:1/-1}.panel{flex-direction:column;align-items:flex-start}}
  </style>
</head>
<body>
  <header>
    <div class="wrap" style="padding-top:.6rem;padding-bottom:.6rem">
      <!-- Top search bar -->
      <form class="bar" method="get" action="listings.php">
        <input class="wide" type="text" name="location" placeholder="Search location (e.g. London, Peterborough)"
               value="<?=h($location)?>">
        <select name="min_price" aria-label="Min Price">
          <option value="">Min Price</option>
          <?php foreach ($priceStops as $p): ?>
            <option value="<?=$p?>" <?=($min!=='' && (int)$min===$p)?'selected':''?>><?= $p? '£'.number_format($p) : 'No min' ?></option>
          <?php endforeach; ?>
        </select>
        <select name="max_price" aria-label="Max Price">
          <option value="">Max Price</option>
          <?php foreach ($priceStops as $p): ?>
            <option value="<?=$p?>" <?=($max!=='' && (int)$max===$p)?'selected':''?>><?= $p? '£'.number_format($p) : 'No max' ?></option>
          <?php endforeach; ?>
        </select>
        <select name="sort" aria-label="Sort">
          <option value="price_desc" <?= $sort==='price_desc'?'selected':'' ?>>Highest Price</option>
          <option value="price_asc"  <?= $sort==='price_asc'?'selected':''  ?>>Lowest Price</option>
          <option value="recent"     <?= $sort==='recent'?'selected':''     ?>>Most Recent</option>
        </select>
        <button type="submit">Search</button>
        <input type="hidden" name="q" value="<?=h($keyword)?>">
      </form>
    </div>
  </header>

  <div class="wrap">
    <div class="crumbs">
      Properties <?= $location? 'for sale in ' . h($location) : 'for sale' ?>
      <?php if ($keyword): ?> › “<?=h($keyword)?>”<?php endif; ?>
    </div>

    <div class="summary">
      <div><?= number_format($total) ?> results</div>
      <div></div>
    </div>



    <!-- Results -->
    <section class="results">
      <?php if (!$rows): ?>
        <div class="card" style="padding:1rem">No properties match your search. Try changing price or location.</div>
      <?php else: foreach ($rows as $r):
        $img  = $r['main_image'] ?: 'assets/placeholder.jpg'; // ensure this file exists
        $addr = trim(
          ($r['location'] ? $r['location'] . ', ' : '') .
          ($r['city'] ?: '') .
          ($r['postcode'] ? ', ' . $r['postcode'] : '')
        );
      ?>
      <article class="card">
        <a class="thumb" href="property.php?id=<?= (int)$r['id'] ?>">
          <img src="<?= h($img) ?>" alt="<?= h($r['title']) ?>">
        </a>
        <div class="content">
          <div class="pill"><?= h($r['property_type_name']) ?></div>
          <h3 class="title">
            <a href="property.php?id=<?= (int)$r['id'] ?>" style="text-decoration:none;color:inherit">
              <?= h($r['title']) ?>
            </a>
          </h3>
          <div class="line"><?= h($addr) ?></div>
          <div class="meta">
            <span> <?= h($r['bedrooms']) ?> bed</span>
            <span> <?= h($r['bathrooms']) ?> bath</span>
            <?php if (!is_null($r['area_sqft'])): ?><span> <?= (int)$r['area_sqft'] ?> sqft</span><?php endif; ?>
            <?php if (!is_null($r['garden_sqft'])): ?><span> <?= (int)$r['garden_sqft'] ?> sqft garden</span><?php endif; ?>
            <?php if (!is_null($r['garage'])): ?><span> Garage: <?= (int)$r['garage'] ?></span><?php endif; ?>
          </div>
          <div class="price"><?= moneyx($r['price']) ?></div>
          <div class="line"><?= h(mb_strimwidth($r['description'] ?? '', 0, 220, '…', 'UTF-8')) ?></div>
          <div class="agent">
            <div>
              Status: <?= h($r['status']) ?>
              <?php if (!empty($r['date_listed'])): ?>
                · Added on <?= h(date('d/m/Y', strtotime($r['date_listed']))) ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </article>
      <?php endforeach; endif; ?>
    </section>

    <!-- Pagination -->
    <nav class="pager" aria-label="Pagination">
      <?php
        $qs = $_GET; unset($qs['page']);
        $base = 'listings.php?' . http_build_query($qs);
        if ($page > 1) echo '<a href="'.$base.'&page='.($page-1).'">&laquo; Prev</a>';
        $start = max(1, $page-2);
        $end   = min($totalPages, $page+2);
        for ($p=$start; $p<=$end; $p++) {
          if ($p === $page) echo '<span class="current">'.$p.'</span>';
          else echo '<a href="'.$base.'&page='.$p.'">'.$p.'</a>';
        }
        if ($page < $totalPages) echo '<a href="'.$base.'&page='.($page+1).'">Next &raquo;</a>';
      ?>
    </nav>
  </div>
</body>
</html>