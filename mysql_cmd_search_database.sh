mysql [DATABASE] --silent -N -e "show tables;"|while read table; do mysql [DATABASE] --silent -N -e "select * from ${table};"|while read line;do if [[ "${line}" == *"[CHECKSTRING]"* ]]; then echo "${table}***${line}";fi;done;done

mysql [DATABASE] --silent -N -e "show tables;"|while read table; do mysql [DATABASE] --silent -N -e "select * from ${table};"|while read line;do if [[ "${line}" == *"[CHECKSTRING]"* ]]; then echo "${table}***${line}";fi;done;done

//example
mysql multione_dbb --silent -N -e "show tables;"|while read table; do mysql multione_dbb --silent -N -e "select * from ${table};"|while read line;do if [[ "${line}" == *"list_bestsellers_slider"* ]]; then echo "${table}***${line}";fi;done;done

mysql -umultione_user -pO3Sz[,pJ?.mq multione_dbb --silent -N -e "show tables;"|while read table; do mysql multione_dbb --silent -N -e "select * from ${table};"|while read line;do if [[ "${line}" == *"list_bestsellers_slider"* ]]; then echo "${table}***${line}";fi;done;done
