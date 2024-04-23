<?php

      require_once 'config.php' ;
      

      class Reservas 
      {

        public static function select(int $id)
        {

            $tabela = "reservas";
            $coluna = "codigo";
            

            $connPdo = new PDO(dbDrive.':host='.dbHost.'; dbname='.dbName, dbUser, dbPass);
            $sql = "select * from $tabela where $coluna = :id" ;  
            $stmt = $connPdo->prepare($sql);  
            $stmt->bindValue(':id' , $id) ;
           

            $stmt->execute() ;
           
            if ($stmt->rowCount() > 0) 
            {



                return $stmt->fetch(PDO::FETCH_ASSOC) ;
                
            }else{               
                throw new Exception("Sem registro de reserva.");
            }

        }
        

        public static function selectAll()
        {
            $tabela = "reservas";
            

            $connPdo = new PDO(dbDrive.':host='.dbHost.'; dbname='.dbName, dbUser, dbPass);
            $sql = "select * from $tabela"  ;
            $stmt = $connPdo->prepare($sql);
            $stmt->execute() ;

            if ($stmt->rowCount() > 0)
            {
                return $stmt->fetchAll(PDO::FETCH_ASSOC) ;
            }else{
                throw new Exception("Sem registros");
            }

        }

      }