<template>
  <div class="price-detail-hover">
    <div class="price-dropdown">
      <div class="price-list">
        <div class="price-item">
          <div class="price-range">0 - 10 M³</div>
          <div class="price-value">Rp 2.500/M³</div>
        </div>

        <div class="price-item">
          <div class="price-range">10 - 20 M³</div>
          <div class="price-value">Rp 3.000/M³</div>
        </div>

        <div class="price-item">
          <div class="price-range">20 - 100 M³</div>
          <div class="price-value">Rp 3.500/M³</div>
        </div>

        <div class="price-item">
          <div class="price-range">&gt; 100 M³</div>
          <div class="price-value">Rp 4.000/M³</div>
        </div>
      </div>

      <div class="price-note">
        <font-awesome-icon icon="info-circle" class="text-blue-500 text-xs mr-1" />
        <span>Biaya abodemen Rp 10.000/bulan</span>
      </div>
    </div>
  </div>
</template>

<script setup>
// Tidak diperlukan script - komponen tampilan langsung
</script>

<style scoped>
.price-detail-hover {
  position: relative;
  display: inline-block;
}

.price-dropdown {
  position: relative;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
  z-index: 1000;
  min-width: 200px;
}

.price-list {
  padding: 0.75rem;
}

.price-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0;
  border-bottom: 1px solid #f1f5f9;
}

.price-item:last-child {
  border-bottom: none;
}

.price-range {
  font-size: 0.75rem;
  font-weight: 500;
  color: #64748b;
}

.price-value {
  font-size: 0.75rem;
  font-weight: 700;
  color: #0b7a9e;
  background: rgba(11, 122, 158, 0.1);
  padding: 0.25rem 0.5rem;
  border-radius: 9999px;
}

.price-note {
  display: flex;
  align-items: center;
  padding: 0.5rem 0.75rem;
  background: #f0f9ff;
  border-top: 1px solid #e2e8f0;
  font-size: 0.7rem;
  color: #64748b;
}

.rotate-180 {
  transform: rotate(180deg);
}
</style>
