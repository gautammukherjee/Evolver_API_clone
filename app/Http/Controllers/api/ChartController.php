<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChartController extends Controller
{
    public function distributionByRelationGrp(Request $request)
    {
        // //echo "edge_select:".$request->input('edge_select');
        // list($day, $month, $year) = explode("-", $request->input('from_date'));
        // $from_date = $year . '-' . $month . '-' . $day;
        //echo $from_date;

        $sql = "SELECT source.edge_group_id, source.Grouped_Edge_Types_Name AS grouped_edge_types_name, COUNT(*) as count
		FROM (SELECT sl.pmid AS pmid, sl.publication_date AS publication_date, sl.title AS title, 
        neslr.pmid AS Node_Edge_Sci_Lit_Rels_pmid,
        nnrtn.name AS Node_Node_Relation_Types, 
        nnrtn.nnrt_id,	  
        nsn.name AS Source_Node_Name,
        nsn.node_id as source_node_id,	  
        ndn.name AS Destination_Node_Name,
        ndn.node_id as destination_node_id,	  
        et.name AS Edge_Types_Name,
	    et.edge_type_id, tet.edge_group_id,
        tet.name AS Grouped_Edge_Types_Name FROM source.sci_lits as sl 
        INNER JOIN graphs.node_edge_sci_lit_rels AS neslr ON sl.pmid = neslr.pmid
        JOIN graphs.node_edge_rels AS nern ON neslr.ne_id = nern.id 
        JOIN graphs.node_node_relation_types AS nnrtn ON nern.nnrt_id = nnrtn.nnrt_id 
        JOIN graphs.nodes AS nsn ON nern.source_node = nsn.node_id 
        JOIN graphs.node_edge_rels AS ner ON nern.id = ner.id 
        JOIN graphs.nodes AS ndn ON nern.destination_node = ndn.node_id 
        -- JOIN graphs.edge_types AS et ON nern.edge_type_id = et.edge_type_id
        -- LEFT JOIN graphs.temp_edge_type_group AS tet ON tet.edge_type_id = nern.edge_type_id 
        JOIN graphs.edge_types et on et.edge_type_id=nern.edge_type_id 
        JOIN graphs.edge_type_group_master tet on tet.edge_group_id=et.edge_group_id";

        // $sql = $sql . "Where sl.publication_date > '2017-06-01' AND";

        $sql = $sql . " Where ";
        $sql = $sql . " nsn.node_id <> ndn.node_id ";
        // $sql = $sql . " AND nsn.name NOT IN ('WAS','IMPACT', 'HR', 'SIT') AND ndn.name NOT IN ('WAS','IMPACT', 'HR', 'SIT')";

        if($request->nnrt_id2 == ""){
            //1. Node select level 1
            if ($request->nnrt_id != "") {
                $sql = $sql . " AND nern.nnrt_id = " . $request->nnrt_id; // pass node-node relation type id
            }

            //2. Source Node
            $sourceNode = collect($request->source_node);
            $sourceNodeImplode = $sourceNode->implode(', ');
            if (!empty($sourceNodeImplode))
                $sql = $sql . " AND nsn.node_id in (" . $sourceNodeImplode . ")"; // pass node-node relation type id
            // }

            //3. Destination Node
            // $destinationNode = collect($request->destination_node);
            // $destinationNodeImplode = $destinationNode->implode(', ');
            // if (!empty($destinationNodeImplode))
            //     $sql = $sql . " AND ndn.node_id in (" . $destinationNodeImplode . ")"; // pass node-node relation type id

            if($request->destination_node_all != 1){
                $destinationNode = collect($request->destination_node);
                $destinationNodeImplode = $destinationNode->implode(', ');
                if (!empty($destinationNodeImplode))
                    $sql = $sql . " AND ndn.node_id in (" . $destinationNodeImplode . ")"; // pass node-node relation type id
            }

            //4. Edge level 1
            $edgeType = collect($request->edge_type_id);
            $edgeTypeImplode = $edgeType->implode(', ');
            if (!empty($edgeTypeImplode))
                $sql = $sql . " AND nern.edge_type_id IN (" . $edgeTypeImplode . ")"; //pass edge_type_id for Level 1
        }else{
            //1. Node select level 2
            if ($request->nnrt_id2 != "") {
                $sql = $sql . " AND nern.nnrt_id = " . $request->nnrt_id2; // pass node-node relation type id
            }

            //2. Source Node 2
            $sourceNode2 = collect($request->source_node2);
            $sourceNode2Implode = $sourceNode2->implode(', ');
            if (!empty($sourceNode2Implode))
                $sql = $sql . " AND nsn.node_id in (" . $sourceNode2Implode . ")"; // pass node-node relation type id
            // }

            //3. Destination Node 2
            $destinationNode2 = collect($request->destination_node2);
            $destinationNode2Implode = $destinationNode2->implode(', ');
            if (!empty($destinationNode2Implode))
                $sql = $sql . " AND ndn.node_id in (" . $destinationNode2Implode . ")"; // pass node-node relation type id

            //4. Edge level 2
            $edgeType2 = collect($request->edge_type_id2);
            $edgeType2Implode = $edgeType2->implode(', ');
            if (!empty($edgeType2Implode))
                $sql = $sql . " AND nern.edge_type_id IN (" . $edgeType2Implode . ")"; //pass edge_type_id for Level 1
        }

        $sql = $sql . " ) AS source";
        $sql = $sql . " GROUP BY 1,2 ORDER BY 3 DESC";
        // echo $sql;
        $result = DB::select($sql);
        return response()->json([
            'nodeSelectsRecords' => $result
        ]);

    } //distributionByRelationGrp() ends


    public function details_of_association_type(Request $request)
    {
        $sql = "SELECT source.nnrt_id,  
        source.Node_Node_Relation_Types AS node_node_relation_types,
        COUNT(*) AS count
        FROM (SELECT sl.pmid AS pmid, sl.publication_date AS publication_date, sl.title AS title, 
        neslr.pmid AS Node_Edge_Sci_Lit_Rels_pmid,
        nnrtn.name AS Node_Node_Relation_Types, 
        nnrtn.nnrt_id,	  
        nsn.name AS Source_Node_Name,
        nsn.node_id as source_node_id,	  
        ndn.name AS Destination_Node_Name,
        ndn.node_id as destination_node_id,	  
        et.name AS Edge_Types_Name,
        tet.name AS Grouped_Edge_Types_Name FROM source.sci_lits as sl 
        INNER JOIN graphs.node_edge_sci_lit_rels AS neslr ON sl.pmid = neslr.pmid
        JOIN graphs.node_edge_rels AS nern ON neslr.ne_id = nern.id 
        JOIN graphs.node_node_relation_types AS nnrtn ON nern.nnrt_id = nnrtn.nnrt_id 
        JOIN graphs.nodes AS nsn ON nern.source_node = nsn.node_id 
        JOIN graphs.node_edge_rels AS ner ON nern.id = ner.id 
        JOIN graphs.nodes AS ndn ON nern.destination_node = ndn.node_id 
        -- JOIN graphs.edge_types AS et ON nern.edge_type_id = et.edge_type_id
        -- LEFT JOIN graphs.temp_edge_type_group AS tet ON tet.edge_type_id = nern.edge_type_id 
        JOIN graphs.edge_types et on et.edge_type_id=nern.edge_type_id 
        JOIN graphs.edge_type_group_master tet on tet.edge_group_id=et.edge_group_id";

        $sql = $sql . " Where ";
        // -- sl.publication_date > '2017-06-01 and ' 
        $sql = $sql . " nsn.node_id <> ndn.node_id";
        // $sql = $sql . " AND nsn.name NOT IN ('WAS','IMPACT', 'HR', 'SIT') AND ndn.name NOT IN ('WAS','IMPACT', 'HR', 'SIT')";

        if($request->nnrt_id2 == ""){
            //1. Node select level 1
            if ($request->nnrt_id != "") {
                $sql = $sql . " AND nern.nnrt_id = " . $request->nnrt_id; // pass node-node relation type id
            }

            //2. Source Node
            $sourceNode = collect($request->source_node);
            $sourceNodeImplode = $sourceNode->implode(', ');
            if (!empty($sourceNodeImplode))
                $sql = $sql . " AND nsn.node_id in (" . $sourceNodeImplode . ")"; // pass node-node relation type id
            // }

            //3. Destination Node
            // $destinationNode = collect($request->destination_node);
            // $destinationNodeImplode = $destinationNode->implode(', ');
            // if (!empty($destinationNodeImplode))
            //     $sql = $sql . " AND ndn.node_id in (" . $destinationNodeImplode . ")"; // pass node-node relation type id

            if($request->destination_node_all != 1){
                $destinationNode = collect($request->destination_node);
                $destinationNodeImplode = $destinationNode->implode(', ');
                if (!empty($destinationNodeImplode))
                    $sql = $sql . " AND ndn.node_id in (" . $destinationNodeImplode . ")"; // pass node-node relation type id
            }

            //4. Edge level 1
            $edgeType = collect($request->edge_type_id);
            $edgeTypeImplode = $edgeType->implode(', ');
            if (!empty($edgeTypeImplode))
                $sql = $sql . " AND nern.edge_type_id IN (" . $edgeTypeImplode . ")"; //pass edge_type_id for Level 1
        }
        else
        {
            //1. Node select level 2
            if ($request->nnrt_id2 != "") {
                $sql = $sql . " AND nern.nnrt_id = " . $request->nnrt_id2; // pass node-node relation type id
            }

            //2. Source Node 2
            $sourceNode2 = collect($request->source_node2);
            $sourceNode2Implode = $sourceNode2->implode(', ');
            if (!empty($sourceNode2Implode))
                $sql = $sql . " AND nsn.node_id in (" . $sourceNode2Implode . ")"; // pass node-node relation type id
            // }

            //3. Destination Node 2
            $destinationNode2 = collect($request->destination_node2);
            $destinationNode2Implode = $destinationNode2->implode(', ');
            if (!empty($destinationNode2Implode))
                $sql = $sql . " AND ndn.node_id in (" . $destinationNode2Implode . ")"; // pass node-node relation type id

            //4. Edge level 2
            $edgeType2 = collect($request->edge_type_id2);
            $edgeType2Implode = $edgeType2->implode(', ');
            if (!empty($edgeType2Implode))
                $sql = $sql . " AND nern.edge_type_id IN (" . $edgeType2Implode . ")"; //pass edge_type_id for Level 2
        }
        
        $sql = $sql . " ) AS source";
        $sql = $sql . " GROUP BY 1,2 ORDER BY 1 ASC";

        // echo $sql;
        $result = DB::select($sql);
        return response()->json([
            'nodeSelectsRecords' => $result
        ]);
    }

    public function pmid_count_with_gene_disease(Request $request)
    {
        $sql = "SELECT DATE_TRUNC('quarter', CAST(source.publication_date AS timestamp)) AS publication_date,
        count(distinct source.pmid) AS count
        FROM
        (
        SELECT sl.pmid AS pmid, sl.publication_date AS publication_date, sl.title AS title, neslr.pmid AS Node_Edge_Sci_Lit_Rels_pmid,
        nnrtn.name AS Node_Node_Relation_Types,
        nsn.name AS Source_Node_Name,
        ndn.name AS Destination_Node_Name,
        et.name AS Edge_Types_Name,
        tet.name AS Grouped_Edge_Types_Name
        FROM source.sci_lits as sl
        JOIN graphs.node_edge_sci_lit_rels AS neslr ON sl.pmid = neslr.pmid
        JOIN graphs.node_edge_rels AS nern ON neslr.ne_id = nern.id
        JOIN graphs.node_node_relation_types AS nnrtn ON nern.nnrt_id = nnrtn.nnrt_id
        JOIN graphs.nodes AS nsn ON nern.source_node = nsn.node_id
        JOIN graphs.node_edge_rels AS ner ON nern.id = ner.id
        JOIN graphs.nodes AS ndn ON nern.destination_node = ndn.node_id
        -- JOIN graphs.edge_types AS et ON nern.edge_type_id = et.edge_type_id
        -- LEFT JOIN graphs.temp_edge_type_group AS tet ON tet.edge_type_id = nern.edge_type_id
        JOIN graphs.edge_types et on et.edge_type_id=nern.edge_type_id 
        JOIN graphs.edge_type_group_master tet on tet.edge_group_id=et.edge_group_id Where";

        //-- sl.publication_date > '2017-06-01' AND
        $sql.=" nsn.node_id <> ndn.node_id ";

        //Check the node level and pass the parameter
        if($request->nnrt_id2 == ""){
            //1. Node select level 1
            if ($request->nnrt_id != "") {
                $sql = $sql . " AND nern.nnrt_id = " . $request->nnrt_id; // pass node-node relation type id
            }

            //2. Source Node
            $sourceNode = collect($request->source_node);
            $sourceNodeImplode = $sourceNode->implode(', ');
            if (!empty($sourceNodeImplode))
                $sql = $sql . " AND nsn.node_id in (" . $sourceNodeImplode . ")"; // pass node-node relation type id
            // }

            //3. Destination Node
            // $destinationNode = collect($request->destination_node);
            // $destinationNodeImplode = $destinationNode->implode(', ');
            // if (!empty($destinationNodeImplode))
            //     $sql = $sql . " AND ndn.node_id in (" . $destinationNodeImplode . ")"; // pass node-node relation type id

            if($request->destination_node_all != 1){
                $destinationNode = collect($request->destination_node);
                $destinationNodeImplode = $destinationNode->implode(', ');
                if (!empty($destinationNodeImplode))
                    $sql = $sql . " AND ndn.node_id in (" . $destinationNodeImplode . ")"; // pass node-node relation type id
            }

            //4. Edge level 1
            $edgeType = collect($request->edge_type_id);
            $edgeTypeImplode = $edgeType->implode(', ');
            if (!empty($edgeTypeImplode))
            $sql = $sql . " AND nern.edge_type_id IN (" . $edgeTypeImplode . ")"; //pass edge_type_id for Level 1

        }else{
            //1. Node select level 2
            if ($request->nnrt_id2 != "") {
                $sql = $sql . " AND nern.nnrt_id = " . $request->nnrt_id2; // pass node-node relation type id
            }

            //2. Source Node 2
            $sourceNode2 = collect($request->source_node2);
            $sourceNode2Implode = $sourceNode2->implode(', ');
            if (!empty($sourceNode2Implode))
                $sql = $sql . " AND nsn.node_id in (" . $sourceNode2Implode . ")"; // pass node-node relation type id
            // }

            //3. Destination Node 2
            $destinationNode2 = collect($request->destination_node2);
            $destinationNode2Implode = $destinationNode2->implode(', ');
            if (!empty($destinationNode2Implode))
                $sql = $sql . " AND ndn.node_id in (" . $destinationNode2Implode . ")"; // pass node-node relation type id

            //4. Edge level 2
            $edgeType2 = collect($request->edge_type_id2);
            $edgeType2Implode = $edgeType2->implode(', ');
            if (!empty($edgeType2Implode))
                $sql = $sql . " AND nern.edge_type_id IN (" . $edgeType2Implode . ")"; //pass edge_type_id for Level 1
        }

        $sql .= ") AS source GROUP BY DATE_TRUNC('quarter', CAST(source.publication_date AS timestamp)) ORDER BY DATE_TRUNC('quarter', CAST(source.publication_date AS timestamp)) ASC";
        // echo $sql;
        $result = DB::select($sql);
        return response()->json([
            'nodeSelectsRecords' => $result
        ]);

    }

    public function distribution_by_relation_grp_get_edge_type_drilldown(Request $request) // only the column drilldown chart when click the column
    {
        $sql = "SELECT source.edge_type_id,
        source.Edge_Types_Name AS edge_types_name,
        COUNT(*)as count FROM
        (SELECT sl.pmid AS pmid,
        sl.publication_date AS publication_date,
        sl.title AS title,
        neslr.pmid AS Node_Edge_Sci_Lit_Rels_pmid,
        nnrtn.name AS Node_Node_Relation_Types,
        nnrtn.nnrt_id,
        nsn.name AS Source_Node_Name,
        nsn.node_id as source_node_id,
        ndn.name AS Destination_Node_Name,
        ndn.node_id as destination_node_id,
        et.name AS Edge_Types_Name,
        et.edge_type_id,
        tet.edge_group_id,
        tet.name AS Grouped_Edge_Types_Name FROM source.sci_lits as sl 
        INNER JOIN graphs.node_edge_sci_lit_rels AS neslr ON sl.pmid=neslr.pmid JOIN graphs.node_edge_rels 
        AS nern ON neslr.ne_id=nern.id JOIN graphs.node_node_relation_types AS nnrtn 
        ON nern.nnrt_id=nnrtn.nnrt_id JOIN graphs.nodes AS nsn ON nern.source_node=nsn.node_id 
        JOIN graphs.node_edge_rels AS ner ON nern.id=ner.id 
        JOIN graphs.nodes AS ndn ON nern.destination_node=ndn.node_id
        JOIN graphs.edge_types et on et.edge_type_id=nern.edge_type_id 
        JOIN graphs.edge_type_group_master tet on tet.edge_group_id=et.edge_group_id 
        Where nsn.node_id<>ndn.node_id ";

        if($request->nnrt_id2 == ""){
            //1. Node select level 1
            if ($request->nnrt_id != "") {
                $sql = $sql . " AND nern.nnrt_id = " . $request->nnrt_id; // pass node-node relation type id
            }

            //2. Source Node
            $sourceNode = collect($request->source_node);
            $sourceNodeImplode = $sourceNode->implode(', ');
            if (!empty($sourceNodeImplode))
                $sql = $sql . " AND nsn.node_id in (" . $sourceNodeImplode . ")"; // pass node-node relation type id
            // }

            //3. Destination Node
            // $destinationNode = collect($request->destination_node);
            // $destinationNodeImplode = $destinationNode->implode(', ');
            // if (!empty($destinationNodeImplode))
            //     $sql = $sql . " AND ndn.node_id in (" . $destinationNodeImplode . ")"; // pass node-node relation type id

            if($request->destination_node_all != 1){
                $destinationNode = collect($request->destination_node);
                $destinationNodeImplode = $destinationNode->implode(', ');
                if (!empty($destinationNodeImplode))
                    $sql = $sql . " AND ndn.node_id in (" . $destinationNodeImplode . ")"; // pass node-node relation type id
            }

        }else{
            //1. Node select level 2
            if ($request->nnrt_id2 != "") {
                $sql = $sql . " AND nern.nnrt_id = " . $request->nnrt_id2; // pass node-node relation type id
            }

            //2. Source Node 2
            $sourceNode2 = collect($request->source_node2);
            $sourceNode2Implode = $sourceNode2->implode(', ');
            if (!empty($sourceNode2Implode))
                $sql = $sql . " AND nsn.node_id in (" . $sourceNode2Implode . ")"; // pass node-node relation type id
            // }

            //3. Destination Node 2
            $destinationNode2 = collect($request->destination_node2);
            $destinationNode2Implode = $destinationNode2->implode(', ');
            if (!empty($destinationNode2Implode))
                $sql = $sql . " AND ndn.node_id in (" . $destinationNode2Implode . ")"; // pass node-node relation type id
        }

         $edgeTypeSel = collect($request->edge_type_id_selected);
         $edgeTypeSelImplode = $edgeTypeSel->implode(', ');
         if (!empty($edgeTypeSelImplode))
             $sql = $sql . " AND nern.edge_type_id in (" . $edgeTypeSelImplode . ")"; //pass edge_type_id for Level 1

        $sql = $sql . " ) AS source";
        $sql = $sql . " GROUP BY 1,2 ORDER BY 3 DESC";
        
        // echo $sql;
        $result = DB::select($sql);
        return response()->json([
            'edgeNamesDrillDown' => $result
        ]);
    } //distributionByRelationGrp() ends

}