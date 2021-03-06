<div class="row">
    <div class="col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <div class="row">
                    <div class="col-sm-10 col-12">
                        <h3 id="quote">Penggajian</h3>
                    </div>
                    <div class="col-sm-2 col-12">
                        <h3 id="quote">
                            <!-- <button class="btn pull-right btn-primary" data-target="#add" data-toggle="modal">Tambah data</button> -->
                            <!-- <a href="<?= base_url('penjualan/form_penjualan_susu')?>" class="btn pull-right btn-primary">Tambah data</a> -->
                        </h3>
                    </div>
                </div>
            </div>
            <div class="x_content">
                <div id="notif">
                    <?php echo $this->session->flashdata('notif_ubah'); ?>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered" id="datatable">
                        <thead>
                            <tr>
                                <th style="width: 4%;">#</th>
                                <th>NIP</th>
                                <th>NPWP</th>
                                <th>Nama Pegawai</th>
                                <th>Jumlah Presensi</th>
                                <th style="width: 15%;" class="text-center">Status</th>
                                <th style="width: 15%;" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php 
                        $no = 1;
                        foreach ($pegawai as $key => $value) { ?>
                        <?php 
                        $this->db->where('nm_pegawai', $value->nama);
                        $detail = $this->db->get('tb_penggajian')->result();
                        // print_r($detail);exit;
                        ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= $value->nip ?></td>
                                <td><?= $value->npwp ?></td>
                                <td><b><?= $value->rfid ?></b>-<?= $value->nama ?></td>
                                <td><?= $value->total ?? '0' ?></td>
                                <td class="text-center">
                                    <?php if (!is_null($value->tgl_gaji)) { ?>
                                        <a href="#" class="btn btn-xs btn-success">Sudah melakukan penggajian</a>
                                    <?php } else { ?>
                                        <a href="#" class="btn btn-xs btn-warning">Belum melakukan penggajian</a>
                                    <?php } ?>
                                    <!-- <a href="" class="btn btn-md btn-default">Detail</a> -->
                                </td>
                                <td class="text-center">
                                    <?php 
                                    $today = date('d'); 
                                    $start = 25;
                                    $end = 28;
                                    if (($today >= $start) && ($today <= $end)){ ?>
                                        <?php if ($value->total >= 0 AND is_null($value->tgl_gaji)) { ?>
                                            <a href="<?= base_url('Penggajian/slip_gaji/'.$value->nip)?>" class="btn btn-xs btn-primary">Pembayaran Gaji</a>
                                        <?php } ?>
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>