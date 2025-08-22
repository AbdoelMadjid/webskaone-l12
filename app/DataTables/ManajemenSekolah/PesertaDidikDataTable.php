<?php

namespace App\DataTables\ManajemenSekolah;

use App\Helpers\ImageHelper;
use App\Models\Kurikulum\DataKBM\PesertaDidikRombel;
use App\Models\ManajemenSekolah\PesertaDidik;
use App\Traits\DatatableHelper;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class PesertaDidikDataTable extends DataTable
{
    use DatatableHelper;
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->filter(function ($query) {
                // pencarian nama
                if (request()->filled('search')) {
                    $query->where('peserta_didiks.nama_lengkap', 'like', '%' . request('search') . '%');
                }

                // filter kompetensi keahlian
                if (request()->filled('kodeKK') && request('kodeKK') !== 'all') {
                    $query->where('peserta_didiks.kode_kk', request('kodeKK'));
                }

                // filter jenis kelamin
                if (request()->filled('jenkelSiswa') && request('jenkelSiswa') !== 'all') {
                    $query->where('peserta_didiks.jenis_kelamin', request('jenkelSiswa'));
                }

                // filter status siswa
                if (request()->filled('statusSiswa') && request('statusSiswa') !== 'all') {
                    $query->where('peserta_didiks.status', request('statusSiswa'));
                } else {
                    // default: hanya ambil siswa Aktif
                    $query->where('peserta_didiks.status', 'Aktif');
                }
            })
            ->addColumn('checkbox', function ($row) {
                return '<input class="form-check-input chk-child" type="checkbox"
                            name="chk_child"
                            value="' . $row->id . '"
                            data-nis="' . $row->nis . '"
                            data-name="' . $row->nama_lengkap . '"
                            data-kode_kk="' . $row->kode_kk . '">';
            })
            ->addColumn('action', function ($row) {
                // Menggunakan basicActions untuk menghasilkan action buttons
                $actions = $this->basicActions($row);
                return view('action', compact('actions'));
            })
            ->addColumn('foto', function ($row) {
                // Menggunakan ImageHelper untuk mendapatkan tag gambar avatar
                // Pastikan kolom 'foto' ada dalam query
                return ImageHelper::getAvatarImageTag(
                    filename: $row->foto,
                    gender: $row->jenis_kelamin,
                    folder: 'peserta_didik',
                    defaultMaleImage: 'siswacowok.png',
                    defaultFemaleImage: 'siswacewek.png',
                    width: 50,
                    class: 'rounded avatar-sm'
                );
            })
            ->addColumn('tempat_tanggal_lahir', function ($row) {
                return $row->tempat_lahir . ', ' . \Carbon\Carbon::parse($row->tanggal_lahir)->format('d-m-Y');
            })
            ->addColumn('status', function ($row) {
                if ($row->status == "Aktif") {
                    $statusbadge = "<span class='badge bg-info'>" . $row->status . "</span>";
                } elseif ($row->status == "Keluar") {
                    $statusbadge = "<span class='badge bg-danger'>" . $row->status . "</span>";
                } elseif ($row->status == "Lulus") {
                    $statusbadge = "<span class='badge bg-warning'>" . $row->status . "</span>";
                }
                return $statusbadge;
            })
            ->addColumn('nis_nisn', function ($row) {
                return $row->nis . '/' . $row->nisn;
            })
            ->addColumn('tingkat_10', function ($row) {
                $data = PesertaDidikRombel::where('nis', $row->nis)
                    ->where('rombel_tingkat', 10)
                    ->first();

                if ($data) {
                    return $data->tahun_ajaran . "<br><span class='badge bg-info'>" . $data->rombel_nama . "</span>";
                }

                return '-';
            })
            ->addColumn('tingkat_11', function ($row) {
                $data = PesertaDidikRombel::where('nis', $row->nis)
                    ->where('rombel_tingkat', 11)
                    ->first();

                if ($data) {
                    return $data->tahun_ajaran . "<br><span class='badge bg-warning'>" . $data->rombel_nama . "</span>";
                }

                return '-';
            })
            ->addColumn('tingkat_12', function ($row) {
                $data = PesertaDidikRombel::where('nis', $row->nis)
                    ->where('rombel_tingkat', 12)
                    ->first();

                if ($data) {
                    return $data->tahun_ajaran . "<br><span class='badge bg-danger'>" . $data->rombel_nama . "</span>";
                }

                return '-';
            })
            ->addIndexColumn()
            ->rawColumns(['checkbox', 'foto', 'tempat_tanggal_lahir', 'nis_nisn', 'status', 'tingkat_10', 'tingkat_11', 'tingkat_12', 'action']);
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(PesertaDidik $model): QueryBuilder
    {
        return $model->newQuery()
            ->join('kompetensi_keahlians', 'peserta_didiks.kode_kk', '=', 'kompetensi_keahlians.idkk')
            ->select([
                'peserta_didiks.*',
                DB::raw('CONCAT(peserta_didiks.kode_kk, " - ", kompetensi_keahlians.singkatan) as kode_kk_singkatan_kk'),
            ])
            ->orderBy('peserta_didiks.kode_kk', 'asc')
            ->orderBy('peserta_didiks.nis', 'asc');
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('pesertadidik-table')
            ->columns($this->getColumns())
            ->ajax([
                'data' =>
                'function(d) {
                    d.search = $(".search").val();
                    d.kodeKK = $("#idKK").val();
                    d.jenkelSiswa = $("#idJenkel").val();
                    d.statusSiswa = $("#idStatus").val();
                }'
            ])
            //->dom('Bfrtip')
            /* ->orderBy(1) */
            ->selectStyleSingle()
            ->parameters([
                //'order' => [[6, 'asc'], [4, 'asc'], [2, 'asc']],
                'lengthChange' => false,
                'searching' => false,
                'pageLength' => 100,
                'paging' => true,
                'scrollCollapse' => false,
                'scrollY' => "calc(100vh - 411px)",
            ]);
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::computed('checkbox')
                ->title('<input class="form-check-input" type="checkbox" id="checkAll" value="option">') // Untuk "Select All"
                ->orderable(false)
                ->searchable(false)
                ->width(10)
                ->addClass('text-center align-middle'),
            Column::computed('kode_kk_singkatan_kk')->title('KK')->addClass('text-center'),
            Column::computed('nis_nisn')->title('NISN / NISN'),
            Column::make('nama_lengkap'),
            Column::computed('tempat_tanggal_lahir')->title('Tempat/ <br> Tanggal Lahir'),
            Column::make('jenis_kelamin'),
            Column::make('tingkat_10'),
            Column::make('tingkat_11'),
            Column::make('tingkat_12'),
            Column::make('status')->addClass('text-center'),
            Column::make('foto')->addClass('text-center'),
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->width(60)
                ->addClass('text-center'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'PesertaDidik_' . date('YmdHis');
    }
}
