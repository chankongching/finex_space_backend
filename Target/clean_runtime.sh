step=1
for((i=0;i<60;i++))
    do 
        rm -rf /var/www/foreign_back/Target/Runtime/*;
        sleep $step;
    done
