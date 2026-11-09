<x-hp.list-header
    :breadcrumbs="$this->getBreadcrumbs()"
    heading="Menu katalog"
    subheading="Kelompok mesin di filter website. Urutan di sini sama dengan yang dilihat pengunjung."
    :actions="$this->getCachedHeaderActions()"
    :metrics="$metrics"
    metrics-label="Ringkasan kelompok mesin"
/>
