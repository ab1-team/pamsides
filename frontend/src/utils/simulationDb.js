const DEFAULT_CUSTOMERS = [
  {
    id: '102808001',
    customer_code: 'PAM-102808001',
    nama: 'Tarsim',
    initials: 'T',
    avatarColor: '#0ea5e9',
    dusun: 'Karangasem',
    meterAwal: 120,
    usageLalu: 15,
    tariff: 'Rumah Tangga 1',
  },
  {
    id: '102808002',
    customer_code: 'PAM-102808002',
    nama: 'Fuji Riyanta',
    initials: 'FR',
    avatarColor: '#06b6d4',
    dusun: 'Mulo',
    dusun_label: 'Mulo Pelanggan',
    meterAwal: 340,
    usageLalu: 22,
    tariff: 'Rumah Tangga 2',
  },
  {
    id: '102808003',
    customer_code: 'PAM-102808003',
    nama: 'Hadi Supriyanto',
    initials: 'HS',
    avatarColor: '#14b8a6',
    dusun: 'Mulo',
    dusun_label: 'Mulo Pelanggan',
    meterAwal: 1140,
    usageLalu: 25,
    tariff: 'Rumah Tangga 1',
  },
  {
    id: '102808004',
    customer_code: 'PAM-102808004',
    nama: 'Siti Aminah',
    initials: 'SA',
    avatarColor: '#ec4899',
    dusun: 'Karangasem',
    meterAwal: 85,
    usageLalu: 12,
    tariff: 'Sosial',
  },
  {
    id: '102808005',
    customer_code: 'PAM-102808005',
    nama: 'Joko Widodo',
    initials: 'JW',
    avatarColor: '#f59e0b',
    dusun: 'Mulo',
    dusun_label: 'Mulo Pelanggan',
    meterAwal: 620,
    usageLalu: 45,
    tariff: 'Niaga',
  },
  {
    id: '102808006',
    customer_code: 'PAM-102808006',
    nama: 'Rini Astuti',
    initials: 'RA',
    avatarColor: '#8b5cf6',
    dusun: 'Karangasem',
    meterAwal: 210,
    usageLalu: 18,
    tariff: 'Rumah Tangga 1',
  },
]

function getDb() {
  let db = localStorage.getItem('pamsides_sim_db')
  if (!db) {
    db = {
      customers: DEFAULT_CUSTOMERS,
      readings: [],
      bills: [
        {
          id: 101,
          customer_id: '102808004',
          customer: DEFAULT_CUSTOMERS[3],
          billing_period_month: 4,
          billing_period_year: 2026,
          meter_reading_start: 73,
          meter_reading_end: 85,
          usage_m3: 12,
          tariff_amount: 54000,
          penalty_amount: 0,
          total_amount: 54000,
          due_date: '2026-05-20',
          status: 'unpaid',
        },
        {
          id: 102,
          customer_id: '102808005',
          customer: DEFAULT_CUSTOMERS[4],
          billing_period_month: 4,
          billing_period_year: 2026,
          meter_reading_start: 575,
          meter_reading_end: 620,
          usage_m3: 45,
          tariff_amount: 225000,
          penalty_amount: 10000,
          total_amount: 235000,
          due_date: '2026-05-20',
          status: 'unpaid',
        },
      ],
    }
    saveDb(db)
  } else {
    db = JSON.parse(db)
  }
  return db
}

function saveDb(db) {
  localStorage.setItem('pamsides_sim_db', JSON.stringify(db))
}

export const simDb = {
  getCustomers() {
    return getDb().customers
  },

  getPendingReadings(params = {}) {
    const db = getDb()
    const month = params.month || new Date().getMonth() + 1
    const year = params.year || new Date().getFullYear()

    return db.customers.filter((c) => {
      const hasReading = db.readings.some(
        (r) => r.customer_id === c.id && r.month === parseInt(month) && r.year === parseInt(year),
      )
      return !hasReading
    })
  },

  saveReading(customerId, meterValue) {
    const db = getDb()
    const customer = db.customers.find((c) => c.id === customerId)
    if (!customer) throw new Error('Customer not found')

    const month = new Date().getMonth() + 1
    const year = new Date().getFullYear()

    db.readings = db.readings.filter(
      (r) => !(r.customer_id === customerId && r.month === month && r.year === year),
    )

    const newReading = {
      id: Date.now(),
      customer_id: customerId,
      meter_start: customer.meterAwal,
      meter_end: meterValue,
      usage: meterValue - customer.meterAwal,
      month,
      year,
      date: new Date().toISOString(),
    }

    db.readings.push(newReading)
    customer.meterAwal = meterValue

    saveDb(db)
    return newReading
  },

  generateBills(params = {}) {
    const db = getDb()
    const month = parseInt(params.month) || new Date().getMonth() + 1
    const year = parseInt(params.year) || new Date().getFullYear()

    const readings = db.readings.filter((r) => r.month === month && r.year === year)

    let generatedCount = 0
    readings.forEach((reading) => {
      const exists = db.bills.some(
        (b) =>
          b.customer_id === reading.customer_id &&
          b.billing_period_month === month &&
          b.billing_period_year === year,
      )
      if (!exists) {
        const customer = db.customers.find((c) => c.id === reading.customer_id)

        const rate = 4500
        const tariffAmount = reading.usage * rate
        const totalAmount = tariffAmount

        const nextMonth = month === 12 ? 1 : month + 1
        const dueYear = month === 12 ? year + 1 : year
        const dueDate = `${dueYear}-${String(nextMonth).padStart(2, '0')}-20`

        db.bills.push({
          id: db.bills.length + 101,
          customer_id: reading.customer_id,
          customer: customer,
          billing_period_month: month,
          billing_period_year: year,
          meter_reading_start: reading.meter_start,
          meter_reading_end: reading.meter_end,
          usage_m3: reading.usage,
          tariff_amount: tariffAmount,
          penalty_amount: 0,
          total_amount: totalAmount,
          due_date: dueDate,
          status: 'unpaid',
        })
        generatedCount++
      }
    })

    saveDb(db)
    return generatedCount
  },

  getBills(params = {}) {
    const db = getDb()
    let result = db.bills
    if (params.status) {
      result = result.filter((b) => b.status === params.status)
    }
    return result
  },

  confirmPayment(billId) {
    const db = getDb()
    const bill = db.bills.find((b) => b.id === parseInt(billId))
    if (bill) {
      bill.status = 'paid'
      saveDb(db)
      return true
    }
    return false
  },
}
