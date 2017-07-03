#include <cstdlib>
#include <cstring>
#include <iostream>
#include <fstream>
#include <string>
#include <sstream>
#include <vector>
#include <map>
#include <unordered_map>
#include <ctime>
#include <fcntl.h>
#include <sys/types.h>
#include <unistd.h>
using namespace std;

/*
argv
1. mainFile
2. fileOf
3. resultFile
*/
int main(int argc, char *argv[])
{
  string pathAggFile,
         path = argv[1],
         pathToListOfAggFiles = argv[2],
         finishFileName = argv[3];

  ofstream finishFile;
  ifstream hAllRows, hViews, hListOfAggFiles;
  unordered_map<string,int> listAllData;
  unordered_map<string,int>::iterator it;

  string pr, name;
  time_t t0, t1, t2;
  time(&t1);
  t0 = t1;
  int views, pos, i = 0;

  hAllRows.open(path.c_str(), ios::in | ios::binary);
  while (!hAllRows.eof()) {
    getline(hAllRows, pr, ' ');
    views = atoi(pr.c_str());
    
    getline(hAllRows, pr);

    if (pr == "") {
       continue;
    }

    listAllData.insert(std::pair<string,int>(pr,views));

  }
  hAllRows.close();

  time(&t2);
  cout<<"start"<<" "<<t2 - t1<<"\n";
  t1 = t2;

  hListOfAggFiles.open(pathToListOfAggFiles.c_str(), ios::in | ios::binary);
  while(getline(hListOfAggFiles, pathAggFile)) {
      if (pathAggFile.c_str() == "") {
        continue;
      }
      hViews.open(pathAggFile.c_str(), ios::in | ios::binary);
      if (!hViews.is_open()) {
         hViews.clear();
         return EXIT_FAILURE;
      }
      i = 0;
      while (!hViews.eof()) {
            getline(hViews, name, ' ');
            getline(hViews, pr);
            views = atoi(pr.c_str());

            it = listAllData.find(name);
            if (it == listAllData.end()) {
              continue;
            }
            (*it).second += views;

            if (++i % 100000 == 0) {
                    cout<<i<<"\n";
            }
      }
      hViews.close();
      hViews.clear();
      
      time(&t2);
      cout<<pathAggFile<<" "<<t2 - t1<<"\n";
      t1 = t2;
  }
  hListOfAggFiles.close();
  
  time(&t2);
  cout<<"save "<<t2 - t1<<"\n";
  t1 = t2;

  finishFile.open(finishFileName.c_str(), ios::out | ios::binary);
  for (it=listAllData.begin();it!=listAllData.end();it++) {
    finishFile<<(*it).second<<" "<<(*it).first<<"\n";
  }
  finishFile.close();
  time(&t2);
  cout<<"finish"<<t2 - t0<<"\n";
  cout<<"SUCCESS\n";

  return EXIT_SUCCESS;
}

