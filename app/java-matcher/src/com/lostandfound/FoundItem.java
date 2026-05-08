package com.lostandfound;

public class FoundItem extends Item {
    public FoundItem(int id, String title, String description, String category, String location) {
        super(id, title, description, category, location, "found");
    }
}
