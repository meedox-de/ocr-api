This is an application/API that is triggered every minute by a cron job. 
It sends files to a free TiKa server, stores the returned OCR data, and forwards it to the requesting application. 

There is a “load balancer” as well as sorting and prioritization of the files to be processed. 
The application essentially serves as an API for the “Thrömer Portal” and the software “Meedox Web/SamyDox.”
