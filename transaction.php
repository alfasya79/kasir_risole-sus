<?php
require_once 'config.php';
startSession();
if (empty($_SESSION['username'])) {
	header('Location: login.php');
	exit;
}
if ($_SESSION['role'] === 'admin') {
	header('Location: dashboard.php');
	exit;
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
	<meta name="mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="default">
	<title>Transaksi</title>
	<link rel="stylesheet" href="style.css">
	<style>
		.pos-layout { display: grid; grid-template-columns: 1fr 380px; gap: 1rem; }
		.pos-products { background:#fff; border-radius:12px; padding:1rem; }
		.pos-grid { display:grid; grid-template-columns: repeat(auto-fill,minmax(220px,1fr)); gap: .75rem; }
		.pos-card { border:1px solid #e2e8f0; border-radius:10px; padding:1rem; cursor:pointer; transition: .2s; background:#fafafa; position:relative; overflow:hidden; }
		.pos-card:hover { transform: translateY(-2px); box-shadow:0 10px 24px rgba(0,0,0,.06) }
		.pos-card.out-of-stock{ opacity:.5; cursor:not-allowed; }
		.pos-name { font-weight:700; color:#2d3748; margin-bottom:.25rem }
		.pos-price { color:#667eea; font-weight:700; }
		.pos-stock { color:#718096; font-size:.9rem }
		.pos-search { width:100%; padding:.75rem; border:2px solid #e2e8f0; border-radius:8px; margin-bottom:.75rem }
		.cart-item { border:1px solid #e2e8f0; border-radius:8px; padding:.5rem .75rem; display:flex; align-items:center; justify-content:space-between; gap:.5rem; background:#f8fafc; }
		.qty-btn { width:28px; height:28px; border:1px solid #cbd5e0; background:#fff; border-radius:6px; font-weight:700; }
		.cart-total { margin-top: .5rem; padding: .75rem; border:2px solid #e2e8f0; border-radius:8px; background:#f7fafc; text-align:center; }
		.btn-primary { background:#667eea; color:#fff; border:none; padding:.75rem 1rem; border-radius:8px; font-weight:700; width:100%; transition: all .2s; }
		.btn-primary:disabled { background:#cbd5e0; }
		.btn-danger { background:#e53e3e; color:#fff; border:none; padding:.6rem .9rem; border-radius:8px; font-weight:700; width:100%; }
		.badge { background:#edf2f7; color:#2d3748; border-radius:999px; padding:.2rem .5rem; font-size:.8rem; font-weight:700; }
		.payment-box { margin-top:.5rem; border:1px dashed #cbd5e0; border-radius:8px; padding:.75rem; background:#fcfcff; }
		.pay-row { display:grid; grid-template-columns: 1fr 1fr; gap:.5rem; margin-bottom:.5rem; }
		.pay-row label { font-size:.9rem; color:#4a5568; }
		.pay-row input { width:100%; padding:.5rem .6rem; border:1px solid #cbd5e0; border-radius:6px; }
		.summary { display:grid; grid-template-columns: 1fr auto; gap:.35rem .75rem; margin-top:.25rem; }
		.summary .payable { font-weight:800; color:#2d3748; }
		.toast { position:fixed; top:20px; right:20px; background:#2d3748; color:#fff; padding:.75rem 1rem; border-radius:8px; box-shadow:0 10px 24px rgba(0,0,0,.15); opacity:0; transform: translateY(-10px); transition:.25s; z-index:9999; }
		.toast.show { opacity:1; transform: translateY(0); }
		.fab-cart{ position:fixed; right:20px; bottom:20px; background:#667eea; color:#fff; border:none; border-radius:999px; padding:.85rem 1rem; font-weight:700; z-index:1100; box-shadow:0 10px 24px rgba(0,0,0,.15); }
		.fab-cart span{ background:#fff; color:#667eea; border-radius:999px; padding:.1rem .45rem; margin-left:.5rem; font-size:.85rem; }
		.cart-modal-backdrop{ position:fixed; inset:0; background:rgba(0,0,0,.45); display:none; align-items:center; justify-content:center; z-index:1200; }
		.cart-modal-backdrop.active{ display:flex; }
		.cart-modal{ background:#fff; width:95%; max-width:420px; border-radius:12px; padding:1rem; box-shadow:0 20px 60px rgba(0,0,0,.25); max-height:90vh; overflow:auto; }
		.cart-modal-header{ display:flex; align-items:center; justify-content:space-between; margin-bottom:.75rem; }
		.cart-close{ background:#e53e3e; color:#fff; border:none; width:36px; height:36px; border-radius:8px; font-size:20px; line-height:0; }
		@media (max-width: 900px) { .pos-layout { grid-template-columns: 1fr } }
	</style>
</head>
<body>
	<button class="mobile-nav-toggle" onclick="toggleSidebar()">☰</button>
	<div class="sidebar-overlay" onclick="closeSidebar()"></div>

	<div class="sidebar-layout">
		<div class="sidebar" id="sidebar">
			<div class="sidebar-header">
				<h2>Risoles & Soes</h2>
			</div>
			<nav class="nav">
				<ul>
						<?php if ($_SESSION['role'] === 'admin'): ?>
							<li><a href="dashboard.php" onclick="closeSidebar()">Dashboard</a></li>
							<li><a href="products.php" onclick="closeSidebar()">Kelola Produk</a></li>
							<li><a href="transaction.php" class="active" onclick="closeSidebar()">Transaksi</a></li>
							<li><a href="pos.php" onclick="closeSidebar()">POS</a></li>
							<li><a href="reports.php" onclick="closeSidebar()">Laporan</a></li>
						<?php elseif ($_SESSION['role'] === 'cashier'): ?>
							<li><a href="dashboard.php" onclick="closeSidebar()">Dashboard</a></li>
							<li><a href="transaction.php" class="active" onclick="closeSidebar()">Transaksi</a></li>
						<?php elseif ($_SESSION['role'] === 'production'): ?>
							<li><a href="products.php" onclick="closeSidebar()">Kelola Produk</a></li>
						<?php endif; ?>
				</ul>
			</nav>
		</div>

		<div class="main-content">
			<div class="container">
				<div class="header"><h1>Transaksi</h1></div>
				<div class="pos-layout">
					<div class="pos-products">
						<input id="pos-search" class="pos-search" placeholder="Cari produk..." />
						<div id="pos-grid" class="pos-grid"></div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<button class="fab-cart" id="open-cart" onclick="openCartModal()">🛒 Keranjang <span id="cart-badge">0</span></button>

	<div id="cart-modal" class="cart-modal-backdrop" onclick="onBackdropClick(event)">
		<div class="cart-modal" role="dialog" aria-modal="true">
			<div class="cart-modal-header">
				<h3>Keranjang <span class="badge" id="cart-count">0</span></h3>
				<button class="cart-close" onclick="closeCartModal()">×</button>
			</div>
			<div id="cart-list" style="display:flex; flex-direction:column; gap:.5rem"></div>
			<div class="payment-box">
				<div class="pay-row" style="grid-template-columns:1fr">
					<div>
						<label>Tunai (Rp)</label>
						<input type="number" id="pay-cash" min="0" placeholder="0" />
					</div>
				</div>
				<div class="summary">
					<div>Subtotal</div><div>Rp <span id="sum-subtotal">0</span></div>
					<div class="payable">Total Bayar</div><div class="payable">Rp <span id="sum-payable">0</span></div>
					<div>Kembalian</div><div>Rp <span id="sum-change">0</span></div>
				</div>
			</div>
			<div class="cart-total">
				<div>Total: Rp <span id="pos-total">0</span></div>
			</div>
			<div style="display:flex; flex-direction:column; gap:.5rem; margin-top:.75rem">
				<button id="btn-pay" class="btn-primary" disabled>Proses Transaksi</button>
				<button id="btn-clear" class="btn-danger">Kosongkan</button>
			</div>
		</div>
	</div>

<script>
let allProducts = [];
let cart = [];
let total = 0;
let cash = 0;

function showToast(msg, type='info'){ const t=document.createElement('div'); t.className='toast'; t.textContent=msg; if(type==='error') t.style.background='#e53e3e'; if(type==='success') t.style.background='#38a169'; document.body.appendChild(t); setTimeout(()=>t.classList.add('show'),10); setTimeout(()=>{ t.classList.remove('show'); setTimeout(()=>document.body.removeChild(t),200); },2500); }

function toggleSidebar(){
	const sb = document.getElementById('sidebar');
	const ov = document.querySelector('.sidebar-overlay');
	if(!sb || !ov) return;
	sb.classList.toggle('mobile-open');
	ov.classList.toggle('active');
}
function closeSidebar(){
	const sb = document.getElementById('sidebar');
	const ov = document.querySelector('.sidebar-overlay');
	if(!sb || !ov) return;
	sb.classList.remove('mobile-open');
	ov.classList.remove('active');
}

function openCartModal(){ const m=document.getElementById('cart-modal'); if(!m) return; m.classList.add('active'); document.body.style.overflow='hidden'; }
function closeCartModal(){ const m=document.getElementById('cart-modal'); if(!m) return; m.classList.remove('active'); document.body.style.overflow=''; }
function onBackdropClick(e){ if(e.target && e.target.id==='cart-modal'){ closeCartModal(); } }
window.addEventListener('keydown', (e)=>{ if(e.key==='Escape'){ closeCartModal(); } });

async function loadProducts() {
	try {
	const res = await fetch('transaction_action.php?action=products');
		allProducts = await res.json();
		renderProducts(allProducts);
	} catch (e) { showToast('Gagal memuat produk','error'); }
}

function renderProducts(list){
	const grid = document.getElementById('pos-grid');
	if (!list || list.length === 0){ grid.innerHTML = '<div style="color:#718096">Tidak ada produk</div>'; return; }
	grid.innerHTML = list.map(p => `
		<div class="pos-card ${p.stock<=0?'out-of-stock':''}" ${p.stock>0 ? `onclick=\"addToCart(${p.id}, '${p.name.replace(/'/g, "&#39;")}', ${p.price}, ${p.stock})\"` : `onclick=\"showToast('Stok habis','error')\"`}>
			<div class="pos-name">${p.name}</div>
			<div class="pos-price">Rp ${Number(p.price).toLocaleString('id-ID')}</div>
			<div class="pos-stock">Stok: ${p.stock}</div>
		</div>
	`).join('');
}

document.getElementById('pos-search').addEventListener('input', e => {
	const q = e.target.value.toLowerCase();
	renderProducts(allProducts.filter(p => p.name.toLowerCase().includes(q)));
});

function addToCart(id, name, price, maxStock){
	const item = cart.find(i=>i.id===id);
	if (item){
		if (item.quantity < maxStock) item.quantity++; else { showToast('Stok tidak cukup','error'); return; }
	} else {
		cart.push({id,name,price,quantity:1,maxStock});
	}
	renderCart();
}

function changeQty(id, d){
	const it = cart.find(i=>i.id===id);
	if (!it) return;
	it.quantity += d;
	if (it.quantity <= 0) cart = cart.filter(i=>i.id!==id);
	if (it.quantity > it.maxStock) { it.quantity = it.maxStock; showToast('Stok tidak cukup','error'); }
	renderCart();
}

function removeItem(id){ cart = cart.filter(i=>i.id!==id); renderCart(); }

function renderCart(){
	const el = document.getElementById('cart-list');
	const btn = document.getElementById('btn-pay');
	const totalEl = document.getElementById('pos-total');
	const countEl = document.getElementById('cart-count');
	const badgeEl = document.getElementById('cart-badge');
	const subEl = document.getElementById('sum-subtotal');
	const payEl = document.getElementById('sum-payable');
	const chgEl = document.getElementById('sum-change');

	if (cart.length===0){
		el.innerHTML = '<div style="color:#718096">Keranjang kosong</div>';
		btn.disabled=true; total=0;
		if(totalEl) totalEl.textContent='0';
		if(countEl) countEl.textContent='0';
		if(badgeEl) badgeEl.textContent='0';
		if(subEl) subEl.textContent='0';
		if(payEl) payEl.textContent='0';
		if(chgEl) chgEl.textContent='0';
		return;
	}
	total = 0;
	let items = 0;
	el.innerHTML = cart.map(it=>{
		const sub = it.price * it.quantity; total += sub; items += it.quantity;
		return `<div class=\"cart-item\">\n            <div>\n                <div style=\"font-weight:700\">${it.name}</div>\n                <div style=\"color:#718096\">Rp ${it.price.toLocaleString('id-ID')}</div>\n            </div>\n            <div style=\"display:flex; align-items:center; gap:.5rem\">\n                <button class=\"qty-btn\" onclick=\"changeQty(${it.id},-1)\">-</button>\n                <input type=\"number\" step=\"1\" min=\"1\" max=\"${it.maxStock}\" value=\"${it.quantity}\" oninput=\"setQty(${it.id}, this.value)\" style=\"width:60px; text-align:center; border:1px solid #cbd5e0; border-radius:6px; padding:4px 6px;\">\n                <button class=\"qty-btn\" onclick=\"changeQty(${it.id},1)\">+</button>\n                <div style=\"min-width:100px; text-align:right; font-weight:700\">Rp ${sub.toLocaleString('id-ID')}<\/div>\n                <button class=\"qty-btn\" style=\"background:#e53e3e;color:#fff;border:none\" onclick=\"removeItem(${it.id})\">×<\/button>\n            <\/div>\n        <\/div>`
	}).join('');
	const payable = total;
	const change = Math.max(0, cash - payable);
	if(countEl) countEl.textContent = String(items);
	if(badgeEl) badgeEl.textContent = String(items);
	if(totalEl) totalEl.textContent = total.toLocaleString('id-ID');
	if(subEl) subEl.textContent = total.toLocaleString('id-ID');
	if(payEl) payEl.textContent = payable.toLocaleString('id-ID');
	if(chgEl) chgEl.textContent = change.toLocaleString('id-ID');
	btn.disabled = !(cart.length && cash >= payable);
}

function setQty(id, value){
	const it = cart.find(i=>i.id===id);
	if(!it) return;
	let q = parseInt(value, 10);
	if(isNaN(q) || q < 1) q = 1;
	if(q > it.maxStock){ q = it.maxStock; showToast('Stok tidak cukup','error'); }
	it.quantity = q;
	renderCart();
}

const cashInput = document.getElementById('pay-cash');

cashInput.addEventListener('input', (e)=>{
	const val = parseInt(e.target.value, 10);
	cash = isNaN(val) ? 0 : Math.max(0, val);
	renderCart();
});

const clearBtn = document.getElementById('btn-clear');
clearBtn.addEventListener('click', ()=>{ if(cart.length && confirm('Kosongkan keranjang?')){ cart=[]; cash=0; document.getElementById('pay-cash').value=''; renderCart(); }});

const payBtn = document.getElementById('btn-pay');
payBtn.addEventListener('click', async ()=>{
	if (!cart.length) return;
	const payable = total;
	if (cash < payable) { showToast('Tunai kurang','error'); return; }
	if (!confirm(`Proses transaksi Rp ${payable.toLocaleString('id-ID')}?`)) return;
	try {
		const res = await fetch('transaction_action.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'process', cart, total: payable}) });
		const data = await res.json();
		if (data.success){
			showToast('Transaksi berhasil','success');
			cart = []; cash = 0;
			document.getElementById('pay-cash').value = '';
			renderCart();
			loadProducts();
			closeCartModal();
		} else {
			showToast(data.message || 'Transaksi gagal','error');
		}
	} catch (e) { showToast('Terjadi kesalahan','error'); }
});

loadProducts();
</script>
</body>
</html>