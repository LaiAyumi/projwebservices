<?php

    include 'Reservas.php';
    class AlunosService 
    {

          public function get( $id = null )
          {
              if ($id){         
                 return Alunos::select($id) ;
              }else{

                 return Alunos::selectAll() ;
              }

          }

          public function post()
          {
              
          }

          public function update()
          {
              
          }

          public function delete()
          {
              
          }
    }