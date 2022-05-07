<?php class Kasir extends CI_Controller
{
    public function __construct() {
        parent::__construct();

        $this->load->model(array(
            'm_masterdata' => 'master',
            'Produk_model' => 'produk',
            'm_transaksi' => 'model'
        ));
    }

    public function index()
    {
        $user = $this->session->nama_lengkap;
        // print_r($user);exit;
        $inv = $this->master->invoice();
        $id_bb = $this->db->query("select id_produk from pos_detail_penjualan where invoice = '$inv'
        and id_produk is not null")->result();
        $total = $this->produk->get_total_detail($inv)->row()->total;
        $ppn = $total * 0.1;
        $gtot = $total + $ppn;
        // print_r($id_bb);exit;
        $data = [
            'kode' => $inv,
            'user' => $user, 
            'produk' => $this->db->get('waserda_produk')->result(),
            'detail' => $this->produk->detail_pos($inv)->result(),
            'total' => $total,
            'ppn' => $ppn,
            'gtot' => $gtot,
            'jenis_anggota' => $this->db->get('waserda_jenis_anggota')->result(), 
            'anggota' => $this->db->get('peternak')->result(),
            'id_bb' => $id_bb,
        ];
        // print_r($data['total']);exit;
        $this->template->load('template', 'kasir/index', $data);
    }

    public function add($barcode)
    {
        # code...
        $produk = $this->db->query("SELECT * FROM waserda_produk WHERE barcode_id = '$barcode'")->row();
        echo json_encode($produk);
    }

    public function list()
    {
        $post = $this->input->post();
        $data = $this->produk->get_item($post);
        echo json_encode($data);
    }

    public function add_detail()
    {
        $invoice = $this->input->post('invoice');
        $barang = $this->input->post('barang');
        $qty = $this->input->post('qty');

        
        if (is_numeric($barang)) {
            # code...
            $produk = $this->db->query("SELECT * FROM waserda_produk WHERE barcode_id = '$barang' ")->row();
            
            if(empty($produk->barcode_id)) 
            {
                $info = [
                    'status' => false,
                    'info' => 'Barcode tidak terdaftar',
                ];
            }
        } else {
            # code...
            $produk = $this->db->query("SELECT * FROM waserda_produk WHERE nama_produk = '$barang' ")->row();
            // print_r($produk);exit;
        }

        $this->db->where('invoice', $invoice);
        $this->db->where('id_produk', $produk->kode);
        $cek = $this->db->get('pos_detail_penjualan')->row();
        // print_r($cek->id_produk);exit;
        // if (empty($cek->id_produk)) {
        //     $this->session->set_flashdata('notif', '<div class="alert alert-warning">Data tidak ditemukan.</div>');
        // }

        $this->db->where('status', 'dalam proses');
        $cek_penjualan = $this->db->get('pos_penjualan')->num_rows();

        if ($cek_penjualan == 0) {
            # code...
            $penjualan = [
                'invoice' => $invoice,
                'status' => 'dalam proses',
                'tanggal' => date('Y-m-d'),
            ];
            $this->db->insert('pos_penjualan', $penjualan);

            $data = [
                'invoice' => $invoice,
                'id_produk' => $produk->kode,
                'jml' => $qty,
                'harga' => $produk->harga_jual,
                'status' => 'dalam proses',
            ];
            $this->db->insert('pos_detail_penjualan', $data);
        } else {
            if (empty($cek->id_produk)) {
                # code...
                $data = [
                    'invoice' => $invoice,
                    'id_produk' => $produk->kode,
                    'jml' => $qty,
                    'harga' => $produk->harga_jual,
                    'status' => 'dalam proses',
                ];
                $this->db->insert('pos_detail_penjualan', $data);
                // var_dump('data baru');exit;

            } else {
                $hasil = $cek->jml + $qty;
                $arr = [
                    'jml' => $hasil
                ];
                $this->db->where('invoice', $invoice);
                $this->db->where('id_produk', $produk->kode);
                $this->db->update('pos_detail_penjualan', $arr);
            }
            $this->session->set_flashdata('notif', '<div class="alert alert-success">Data berhasil ditambahkan.</div>');
        }
        redirect('Kasir');
        // print_r($data);exit;
    }

    public function detail_barcode($qty, $invoice, $barcode)
    {
        $produk = $this->db->query("SELECT * FROM waserda_produk WHERE barcode_id = '$barcode' ")->row();

        if ($produk) {
            # code...
            $data = [
                'invoice' => $invoice,
                'id_produk' => $produk->kode,
                'jml' => $qty,
                'harga' => $produk->harga_jual,
                'status' => 'dalam proses',
            ];
            if ($this->db->insert('pos_detail_penjualan', $data)) {
                # code...
                $result = [
                    'status' => true, 
                    'info'   => 'Berhasil disimpan.'
                ];
            } else {
                # code...
                $result = [
                    'status' => true, 
                    'info'   => 'Gagal disimpan.'
                ];
            }
        } else {
            # code...
            $result = [
                'status' => false, 
                'info'   => 'No Barcode Recoreded'
            ];
        }
        echo json_encode($result);
    }

    public function update_qty($kode, $invoice, $qty)
    {
        // $qty_update = $this->input->post('qty_update');
        // print_r($qty_update);exit;
        $arr = [
            'jml' => $qty
        ];
        $this->db->where('invoice', $invoice);
        $this->db->where('id_produk', $kode);
        $this->db->update('pos_detail_penjualan', $arr);

        echo json_encode('Sukses Update');
        // redirect('Kasir');
    }

    public function checkout()
    {
        $id_bb = $this->input->post('id_bb');
        $kode = $this->input->post('kode');
        $jenis = $this->input->post('jenis');
        $pembeli = $this->input->post('pembeli');
        $tipe = $this->input->post('tipe');
        $pembayaran = $this->input->post('pembayaran');
        $kembalian = $this->input->post('kembalian');
        $total = $this->input->post('total');
        $status = ($tipe == 'kredit') ? 'kredit' : 'terbayar';

        $anggota = $this->input->post('anggota');
        // print_r($jenis);exit;

        $ppn = $this->input->post('ppn');
        $total_trans = $this->input->post('total_trans');

        $data = [
            'total' => $total,
            'nama_pembeli' => $pembeli,
            'jenis_pembayaran' => $tipe,
            'kembalian' => $kembalian,
            'pembayaran' => $pembayaran,
            'ppn' => $ppn,
            'total_trans' => $total_trans,
            'id_detail_jenis_anggota' => $jenis,
            'status' => $status
        ];
        // print_r($kode);exit;
        $this->db->where('invoice', $kode);
        $this->db->update('pos_penjualan', $data);

        if ($jenis == 1 && $tipe == 'kredit') {

            if ($anggota == 'pegawai') {
                $status_kredit = [
                    'status_kredit' => 1
                ];
                $this->db->where('nama', $pembeli);
                $this->db->update('pegawai', $status_kredit);
            } else {
                $status_kredit = [
                    'status_kredit' => 1
                ];
                $this->db->where('nama_peternak', $pembeli);
                $this->db->update('peternak', $status_kredit);
            }

            $tb_kredit = [
                'invoice' => $kode,
                'nama' => $pembeli,
                'jenis_anggota' => $anggota,
                'nominal' => $pembayaran,
            ];
            $this->db->insert('waserda_pembayaran_kredit', $tb_kredit);
        }

        // masih belum bisa insert ke detail
        // $data = [
        //     'total' => $total,
        //     'nama_pembeli' => $pembeli,
        //     'jenis_pembayaran' => $tipe,
        //     'kembalian' => $kembalian,
        //     'pembayaran' => $pembayaran,
        //     'ppn' => $ppn,
        //     'total_trans' => $total_trans,
        //     'id_detail_jenis_anggota' => $jenis,
        //     'status' => $status
        // ];
        // // print_r($data);exit;
        // $this->db->where('invoice', $kode);
        // $this->db->update('pos_penjualan', $data);

        // print_r($tipe);exit;

        // gak dipakai
        // if ($tipe == 'kredit') {
        //     # code...
        //     $tb_kredit = [
        //         'invoice' => $kode,
        //         'nama' => $pembeli,
        //         'nominal' => $pembayaran,
        //     ];
        //     $this->db->insert('waserda_pembayaran_kredit', $tb_kredit);
        // }

        $this->db->where('invoice', $kode);
        $this->db->where('id_produk !=', NULL);
        $cek_invoice = $this->db->get('pos_detail_penjualan')->result();

        $where = [];
        $bb = [];
        foreach ($id_bb as $key => $value) {
            $where = array(
                'kode' => $value
            );
            // ambil stok akhir
            $this->db->where(['kode' => $value]);
            $jumlah = $this->db->get('waserda_produk')->row()->jml;

            $bb = array(
                'jml' => $jumlah - $cek_invoice[$key]->jml,
            );
            $this->db->where($where);
            $this->db->update('waserda_produk', $bb);
        }

        // insert ke buku kas kecil
        if ($total_trans < 10000000) {
            $date_now = date('Y-m-d');
            $this->model->insert_buku_kas_kecil($kode, $date_now, $total_trans, 'Penjualan', 'd');
        }

        $this->session->set_flashdata('notif', '<div class="alert alert-success">Pembayaran berhasil.</div>');

        redirect('Kasir');
    }

    public function jenis($tipe)
    {
        if ($tipe) {
            echo $this->produk->jenis_anggota($tipe);
        }
    }

    public function pos_penjualan()
    {
        $this->template->load('template', 'waserda/penjualan/index');
    }

    public function pmb_kredit()
    {
        $this->db->order_by('id', 'desc');
        $kredit = $this->db->get('waserda_pembayaran_kredit')->result();
        $kode = $this->produk->kd_pembayaran_kredit();
        // print_r($kode);exit;
        $data = [
            'kode' => $kode,
            'list' => $kredit,
        ];
        $this->template->load('template', 'waserda/pmb_kredit/index', $data);
    }

    public function save_pmb_kredit()
    {
        $kd_pembayaran = $this->input->post('kd_pembayaran');
        $tgl_pembayaran = $this->input->post('tgl_pembayaran');
        $invoice = $this->input->post('invoice');
        $nm_pembeli = $this->input->post('nm_pembeli');
        $total = $this->input->post('total');

        $anggota = $this->input->post('anggota');
        
        // kirim ke db pengajuan jurnal 
        $pengajuan = [
            'kode' => $kd_pembayaran,
            'tanggal' => $tgl_pembayaran,
            'nominal' => $total,
            'jenis' => $anggota,
        ];
        $this->db->insert("pengajuan_jurnal", $pengajuan);

        // ubah status pembayaran kredit 
        $status = [
            'status' => 2
        ];
        $this->db->where('invoice', $invoice);
        $this->db->update('waserda_pembayaran_kredit', $status);

        
        if ($anggota == 'pegawai') {
            $status_kredit = [
                'status_kredit' => 0
            ];
            $this->db->where('nama', $nm_pembeli);
            $this->db->update('pegawai', $status_kredit);
        } else {
            $status_kredit = [
                'status_kredit' => 0
            ];
            $this->db->where('nama_peternak', $nm_pembeli);
            $this->db->update('peternak', $status_kredit);
        }

        $data = [
            'id_pembayaran' => $kd_pembayaran, 
            'tanggal' => $tgl_pembayaran, 
        ];
        $this->db->where('invoice', $invoice);
        $this->db->update('waserda_pembayaran_kredit', $data);

        $status = [
            'status_kredit' => 'lunas',
        ];
        $this->db->where('invoice', $invoice);
        $this->db->update('pos_penjualan', $status);

        redirect('Kasir/pmb_kredit');
    }

    public function list_penjualan()
    {
        $list = $this->db->query('select * from pos_penjualan where nama_pembeli is not null order by date_payment desc')->result();
        $data = [
            'list' => $list,
        ];
        $this->template->load('template', 'waserda/penjualan/index', $data);
    }

    public function detail_print($invoice)
    {
        $detail = $this->db->query('select pdp.*, wp.nama_produk, pp.tanggal, pp.date_payment from pos_detail_penjualan pdp 
        join pos_penjualan pp on pdp.invoice = pp.invoice 
        join waserda_produk wp on pdp.id_produk = wp.kode 
        where pp.nama_pembeli is not null
        and pdp.invoice = "'.$invoice.'"
        order by pp.date_payment desc')->result();
        $data = [
            'detail' => $detail
        ];
        // print_r($detail);exit;
        $this->template->load('template', 'waserda/penjualan/detailPrint', $data);
    }

    public function pdf($invoice)
    {
        $detail = $this->db->query('select pdp.*, wp.nama_produk, pp.tanggal, pp.date_payment, pp.pembayaran, pp.kembalian, pp.total_trans, pp.ppn, pp.total 
        from pos_detail_penjualan pdp 
        join pos_penjualan pp on pdp.invoice = pp.invoice 
        join waserda_produk wp on pdp.id_produk = wp.kode 
        where pp.nama_pembeli is not null
        and pdp.invoice = "'.$invoice.'"
        order by pp.date_payment desc')->result();
        $pnj = $this->db->query('select * from pos_penjualan where invoice = "'.$invoice.'"')->row();
        $data = [
            'title' => 'pdf', 
            'detail' => $detail,
            'penjualan' => $pnj,
        ];
    
        $this->load->library('pdf');
        $this->pdf->setPaper('a7', 'potrait');
        $this->pdf->filename = "laporan-petanikode.pdf";
        $this->pdf->load_view('waserda/penjualan/laporan_pdf', $data);
    }

    // test
    public function hapus_detail($id)
    {
        $this->db->where('id', $id);
        $data = $this->db->delete('pos_detail_penjualan');

        if ($data) {
            echo json_encode($data);
        }
    }
}
?>