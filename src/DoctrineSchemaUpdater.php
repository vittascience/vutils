<?php

namespace Utils;

use Doctrine\ORM\Tools\SchemaTool;

class DoctrineSchemaUpdater{

    private $entityManager;
    private $tool;
    private $entities = [];
    /**
     * always keep the $preserveOtherTablesInDb to true
     * unless you want to delete other tables in db
     * @var boolean
     */
    private $preserveOtherTablesInDb = true;

    /**
     * get the entityManager and the SchemaTool
     *
     * @return  void
     */
    public function __construct(){
        require(__DIR__."/../../../../bootstrap.php");
        $this->entityManager = $entityManager;
        $this->tool = new SchemaTool($entityManager);
    }

    /**
     * set the entites array
     *
     * @param   array  $entityArray  entities format => User::class
     *
     * @return  self             
     */
    public function setEntityArray($entityArray){
        foreach($entityArray as $entity){
            array_push(
                $this->entities,
                $this->entityManager->getClassMetadata($entity)
            );
        }
        return $this;
    }

    /**
     * display Sql queries without running them
     *
     * @return  void  
     */
    public function displaySql(){
        dd($this->tool->getUpdateSchemaSql(
            $this->entities,
            $this->preserveOtherTablesInDb
        ));
    }

    /**
     *  DANGEROUS AREA
     * run Sql queries to update the database
     * 
     * The 2nd parameter "$preserveOtherTablesInDb" of "updateSchema" 
     * tell doctrine to RUN additional DROP queries
     * true = do not RUN DROP queries 
     * (just RUN UPDATE Queries for the entity(ies) listed in $classes array)
     * 
     * false  = run DROP and UPDATE queries
     * (RUN both DROP and UPDATE Queries for all entities)
     * meaning => only entities listed in $this->entities array will be preserved, 
     * all others tables can be deleted from the database
     * 
     * uncoment the line below only when you are sure and comment it again just after
     * @return  void  
     */
    public function runSqlToUpdate(){
        $this->tool->updateSchema(
            $this->entities,
            $this->preserveOtherTablesInDb
        );
    }
}