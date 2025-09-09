
    
    <style>
        body {
            background-color: #e8f5e9;
        }
        .container {
            margin-top: 30px;
        }
        .panel-heading {
            background-color: #388e3c !important;
            color: white !important;
        }
        .table > thead {
            background-color: #43a047;
            color: white;
        }
    </style>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>

    <div class="container">
        <div class="panel panel-success">
            <div class="panel-heading text-center">
                <h3>Relatório de Cadastro</h3>
            </div>
            <div class="panel-body">
                <table id="tabela" class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Cadastro</th>
                            <th>Perfil</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $res = $pdoS->query("SELECT 
                            RTRIM(CG.COD_CADASTRO)+' - '+RTRIM(CG.NOME_CADASTRO) AS CADASTRO,
                            ISNULL(RTRIM(CG.COD_CADASTRO_TRIB)+' - '+RTRIM(PERF.NOME_CADASTRO),'SEM PERFIL') AS PERFIL
                            FROM TBCADASTROGERAL CG
                            LEFT JOIN TBCADASTROGERAL PERF ON CG.COD_CADASTRO_TRIB=PERF.COD_CADASTRO
                            WHERE CG.TIPO_CADASTRO='P'
                            ORDER BY 1");
                        
                        while ($r = $res->fetch(PDO::FETCH_ASSOC)):
                            echo "<tr>
                                    <td>{$r['CADASTRO']}</td>
                                    <td>{$r['PERFIL']}</td>
                                  </tr>";
                        endwhile;
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
   
    
           <script>
                $(document).ready(function() {
                    $('#tabela').DataTable({
                        dom: 'Bfrtip',
                        paging: false,
                        buttons: [{
                                extend: 'print',
                                text: 'Imprimir',
                                footer: true, // Inclui o rodapé na impressão
                                exportOptions: {
                                    columns: ':not(:last-child)' // Exclui a última coluna (Ações) da impressão
                                },
                                customize: function(win) {
                                    // Adiciona estilo ao rodapé para que ele apareça na impressão
                                    $(win.document.body).find('tfoot').css('display', 'table-footer-group');
                                }
                            },
                            'csv',
                            'excel',
                            'pdf'
                        ],
                        order: [
                            [3, 'desc']
                        ],
                    });
                });
            </script>
