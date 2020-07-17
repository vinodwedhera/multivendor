import { Component, OnInit, Input } from "@angular/core";
import { LandingService } from "src/app/services/landing/landing.service";
import { ArticlesService } from 'src/app/services/articles/articles.service';
import { SourcesService } from 'src/app/services/sources/sources.service';
@Component({
  selector: "app-header",
  templateUrl: "./header.component.html",
  styleUrls: ["./header.component.scss"]
})
export class HeaderComponent implements OnInit {
  // @Input() showTitleBlock: string;

  // public username: string;
  // public titleBlockValue: string;

  // constructor(private landingService: LandingService) {}

  // ngOnInit() {
  //   this.landingService.getTitleBlockValue().subscribe(data => {
  //     this.titleBlockValue = data;
  //   });

  //   this.landingService.getLoginValue().subscribe(data => {
  //     this.username = data;
  //   });
    public articlesList: any;
  public sourcesList: any;
  public currentSource: any;
  public newsOnPage: number;
  public newsCount: number;
  public showLocal: boolean;
  public filterValue: string;
  public isAuthorised: boolean;

  constructor(private landingService: LandingService, private articlesService: ArticlesService, private soucesService: SourcesService) {
    this.newsCount = 8;
    this.newsOnPage = this.newsCount;
    this.showLocal = false;
    this.articlesList = [];

  
    
  }
  showTitleBlock(title: string) {
    this.landingService.updateTitleBlockValue(title);
  }



  setFilterValue(value: string){
    this.filterValue = value;
    this.newsOnPage = this.newsCount;
  }
  ngOnInit() {

  }
  }
  

