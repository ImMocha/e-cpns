@unless ($package->trashed())
    @if ($package->is_active)
        <form method="POST" action="{{ route('admin.packages.deactivate', $package) }}" data-content-confirm data-confirm-title="Nonaktifkan paket?" data-confirm-body="Pembelian dan aktivasi baru dihentikan. Akses peserta yang sudah aktif tetap berlaku." data-confirm-ok="Ya, nonaktifkan">
            @csrf
            @method('PATCH')
            <button type="submit" class="btn btn--ghost btn--sm">Nonaktifkan</button>
        </form>
    @endif
    <form method="POST" action="{{ route('admin.packages.destroy', $package) }}" data-content-confirm data-confirm-title="Hapus paket?" data-confirm-body="Paket diarsipkan dan dihapus dari katalog. Akses peserta yang sudah aktif serta riwayat transaksi tetap tersimpan." data-confirm-ok="Ya, hapus">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn--ghost btn--sm" style="color:#B3282C">Hapus paket</button>
    </form>
@endunless
