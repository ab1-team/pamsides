import { describe, it, expect, beforeEach } from 'vitest'
import { useNotification } from './useNotification'

describe('useNotification', () => {
  beforeEach(() => {
    const { hideNotification, handleClose } = useNotification()
    hideNotification()
    handleClose()
  })

  it('initial state has show=false', () => {
    const { notificationState } = useNotification()
    expect(notificationState.value.show).toBe(false)
  })

  it('showNotification sets show to true', () => {
    const { showNotification, notificationState } = useNotification()
    showNotification({ title: 'Hello', message: 'World' })
    expect(notificationState.value.show).toBe(true)
    expect(notificationState.value.title).toBe('Hello')
    expect(notificationState.value.message).toBe('World')
  })

  it('hideNotification sets show to false', () => {
    const { showNotification, hideNotification, notificationState } = useNotification()
    showNotification({ title: 'Test' })
    hideNotification()
    expect(notificationState.value.show).toBe(false)
  })

  it('success sets type=success and autoClose=true with 3s delay', () => {
    const { success, notificationState } = useNotification()
    success('Berhasil', 'Data disimpan')
    expect(notificationState.value.type).toBe('success')
    expect(notificationState.value.autoClose).toBe(true)
    expect(notificationState.value.autoCloseDelay).toBe(3000)
  })

  it('error sets type=error and 5s delay', () => {
    const { error, notificationState } = useNotification()
    error('Gagal', 'Terjadi kesalahan')
    expect(notificationState.value.type).toBe('error')
    expect(notificationState.value.autoCloseDelay).toBe(5000)
  })

  it('warning sets type=warning', () => {
    const { warning, notificationState } = useNotification()
    warning('Peringatan', 'Hati-hati')
    expect(notificationState.value.type).toBe('warning')
  })

  it('info sets type=info', () => {
    const { info, notificationState } = useNotification()
    info('Info', 'Catatan')
    expect(notificationState.value.type).toBe('info')
  })

  it('options override defaults', () => {
    const { success, notificationState } = useNotification()
    success('OK', 'Done', { autoClose: false, autoCloseDelay: 9999 })
    expect(notificationState.value.autoClose).toBe(false)
    expect(notificationState.value.autoCloseDelay).toBe(9999)
  })

  it('handleConfirm resolves pending confirm with true', async () => {
    const { confirm, handleConfirm, notificationState } = useNotification()
    const promise = confirm({ title: 'Yakin?' })
    expect(notificationState.value.show).toBe(true)
    expect(notificationState.value.showActions).toBe(true)

    handleConfirm()
    await expect(promise).resolves.toBe(true)
    expect(notificationState.value.show).toBe(false)
  })

  it('handleCancel rejects pending confirm with false', async () => {
    const { confirm, handleCancel } = useNotification()
    const promise = confirm({ title: 'Yakin?' })
    handleCancel()
    await expect(promise).rejects.toBe(false)
  })

  it('handleClose clears pending confirm without resolving', () => {
    const { confirm, handleClose, notificationState } = useNotification()
    confirm({ title: 'Yakin?' })
    expect(notificationState.value.show).toBe(true)
    handleClose()
    expect(notificationState.value.show).toBe(false)
  })
})
